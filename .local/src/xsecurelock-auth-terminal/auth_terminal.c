#define _GNU_SOURCE

#include <X11/Xlib.h>
#include <X11/Xutil.h>
#include <errno.h>
#include <fcntl.h>
#include <poll.h>
#include <signal.h>
#include <stdio.h>
#include <stdlib.h>
#include <string.h>
#include <sys/mman.h>
#include <sys/prctl.h>
#include <sys/types.h>
#include <sys/wait.h>
#include <time.h>
#include <unistd.h>

#define MAX_INPUT 4096
#define FRAME_W 640
#define FRAME_H 128
#define STROKE_WIDTH 3
#define ANIM_MS 420
#define BLINK_MS 180
#define PROMPT_PAUSE_MS 500
#define RESPONSE_PAUSE_MS 500
#define FINAL_PAUSE_MS 1000

enum packet_type {
  PACKET_INFO = 'i',
  PACKET_ERROR = 'e',
  PACKET_USERNAME = 'U',
  PACKET_PASSWORD = 'P',
};

static unsigned long now_ms(void) {
  struct timespec ts;
  clock_gettime(CLOCK_MONOTONIC, &ts);
  return (unsigned long)ts.tv_sec * 1000UL + (unsigned long)ts.tv_nsec / 1000000UL;
}

static void wipe(char *buf, size_t len) {
  volatile char *p = (volatile char *)buf;
  while (len--) *p++ = 0;
}

static int read_full(int fd, void *buf, size_t len) {
  size_t done = 0;
  while (done < len) {
    ssize_t n = read(fd, (char *)buf + done, len - done);
    if (n == 0) return 0;
    if (n < 0 && errno == EINTR) continue;
    if (n < 0) return -1;
    done += (size_t)n;
  }
  return 1;
}

static int read_packet(int fd, char *type, char **message) {
  char header[64];
  size_t used = 0;
  *message = NULL;
  for (;;) {
    char c;
    int result = read_full(fd, &c, 1);
    if (result <= 0) return result;
    if (used + 1 >= sizeof(header)) return -1;
    header[used++] = c;
    if (c == '\n') break;
  }
  header[used] = 0;
  unsigned long len = 0;
  if (sscanf(header, " %c %lu", type, &len) != 2 || len > 65536) return -1;
  char *buf = calloc(len + 1, 1);
  if (!buf) return -1;
  int result = read_full(fd, buf, len);
  if (result <= 0) {
    free(buf);
    return result;
  }
  char newline;
  result = read_full(fd, &newline, 1);
  if (result <= 0 || newline != '\n') {
    free(buf);
    return -1;
  }
  *message = buf;
  return 1;
}

static int write_packet(int fd, char type, const char *message) {
  size_t len = strlen(message);
  char header[64];
  int header_len = snprintf(header, sizeof(header), "%c %zu\n", type, len);
  if (header_len < 0) return 0;
  if (write(fd, header, (size_t)header_len) != header_len) return 0;
  if (len && write(fd, message, len) != (ssize_t)len) return 0;
  return write(fd, "\n", 1) == 1;
}

static int start_authproto(int *request_fd, int *response_fd, pid_t *pid) {
  int request_pipe[2];
  int response_pipe[2];
  if (pipe(request_pipe) || pipe(response_pipe)) return 0;
  pid_t child = fork();
  if (child < 0) return 0;
  if (child == 0) {
    const char *path = getenv("XSECURELOCK_AUTHPROTO");
    if (!path || !*path) path = "/usr/libexec/xsecurelock/authproto_pam";
    dup2(response_pipe[0], STDIN_FILENO);
    dup2(request_pipe[1], STDOUT_FILENO);
    close(request_pipe[0]);
    close(request_pipe[1]);
    close(response_pipe[0]);
    close(response_pipe[1]);
    execl(path, path, (char *)NULL);
    _exit(127);
  }
  close(request_pipe[1]);
  close(response_pipe[0]);
  *request_fd = request_pipe[0];
  *response_fd = response_pipe[1];
  *pid = child;
  return 1;
}

struct ui {
  Display *display;
  Window window;
  GC gc;
  unsigned long fg;
  unsigned long red;
  unsigned long bg;
  XFontStruct *font;
  int width;
  int height;
  int frame_w;
  int frame_h;
  int password_prompt;
  char prompt_type;
  int error;
  char message[256];
  char input[MAX_INPUT];
  size_t input_len;
  char pending_response[MAX_INPUT];
  char pending_type;
  int response_pending;
  unsigned long response_due_ms;
  int secrets_locked;
};

static void protect_secret_buffers(struct ui *ui) {
  // Best effort: preserve unlock availability if memlock policy is restrictive.
  madvise(ui->input, sizeof(ui->input), MADV_DONTDUMP);
  madvise(ui->pending_response, sizeof(ui->pending_response), MADV_DONTDUMP);
  int input_locked = mlock(ui->input, sizeof(ui->input)) == 0;
  int pending_locked = mlock(ui->pending_response, sizeof(ui->pending_response)) == 0;
  if (input_locked && pending_locked) {
    ui->secrets_locked = 1;
  } else {
    if (input_locked) munlock(ui->input, sizeof(ui->input));
    if (pending_locked) munlock(ui->pending_response, sizeof(ui->pending_response));
  }
  prctl(PR_SET_DUMPABLE, 0, 0, 0, 0);
}

static void draw(struct ui *ui, double progress, int point_only, int blink_on) {
  int cx = ui->width / 2;
  int cy = ui->height / 2;
  XSetForeground(ui->display, ui->gc, ui->bg);
  XFillRectangle(ui->display, ui->window, ui->gc, 0, 0, (unsigned)ui->width, (unsigned)ui->height);
  XSetLineAttributes(ui->display, ui->gc, STROKE_WIDTH, LineSolid, CapButt, JoinMiter);
  XSetForeground(ui->display, ui->gc, ui->error ? ui->red : ui->fg);

  if (point_only) {
    if (blink_on) {
      XFillRectangle(ui->display, ui->window, ui->gc,
                     cx - STROKE_WIDTH / 2, cy - STROKE_WIDTH / 2,
                     STROKE_WIDTH, STROKE_WIDTH);
    }
  } else if (progress < 0.5) {
    double p = progress * 2.0;
    int half_line = (int)((ui->frame_w / 2.0) * p);
    XDrawLine(ui->display, ui->window, ui->gc, cx - half_line, cy, cx + half_line, cy);
  } else {
    double p = (progress - 0.5) * 2.0;
    // Horizontal expansion already completed. Keep width fixed while height grows.
    int half_w = ui->frame_w / 2;
    int half_h = (int)((ui->frame_h / 2.0) * p);
    if (half_h < STROKE_WIDTH) half_h = STROKE_WIDTH;
    XDrawRectangle(ui->display, ui->window, ui->gc, cx - half_w, cy - half_h,
                   (unsigned)(half_w * 2), (unsigned)(half_h * 2));
    if (p > 0.85 && ui->password_prompt) {
      char masked[MAX_INPUT];
      size_t n = ui->input_len < sizeof(masked) - 1 ? ui->input_len : sizeof(masked) - 1;
      memset(masked, '*', n);
      masked[n] = 0;
      XSetForeground(ui->display, ui->gc, ui->error ? ui->red : ui->fg);
      XDrawString(ui->display, ui->window, ui->gc, cx - half_w + 24, cy + 5, masked, (int)n);
      if (!ui->error) {
        int text_width = ui->font ? XTextWidth(ui->font, masked, (int)n) : (int)n * 8;
        XDrawString(ui->display, ui->window, ui->gc,
                    cx - half_w + 24 + text_width, cy + 5, "_", 1);
      }
    }
  }
  if (ui->message[0] && progress >= 0.85) {
    XDrawString(ui->display, ui->window, ui->gc, cx - ui->frame_w / 2 + 24,
                cy - ui->frame_h / 2 + 24, ui->message, (int)strlen(ui->message));
  }
  XFlush(ui->display);
}

static int ui_init(struct ui *ui) {
  const char *window_id = getenv("XSCREENSAVER_WINDOW");
  if (!window_id) return 0;
  Window parent = (Window)strtoull(window_id, NULL, 0);
  ui->display = XOpenDisplay(NULL);
  if (!ui->display) return 0;
  XWindowAttributes attrs;
  if (!XGetWindowAttributes(ui->display, parent, &attrs)) return 0;
  ui->width = attrs.width;
  ui->height = attrs.height;
  ui->frame_w = FRAME_W < ui->width - 80 ? FRAME_W : ui->width - 80;
  ui->frame_h = FRAME_H;
  ui->fg = WhitePixel(ui->display, DefaultScreen(ui->display));
  ui->red = ui->fg;
  ui->bg = BlackPixel(ui->display, DefaultScreen(ui->display));
  XColor color, exact;
  if (XAllocNamedColor(ui->display, DefaultColormap(ui->display, DefaultScreen(ui->display)), "#ff4d4d", &color, &exact)) ui->red = color.pixel;
  // XSecureLock keeps its protected main window above the saver. Draw there;
  // children would sit behind that black window.
  ui->window = parent;
  ui->gc = XCreateGC(ui->display, ui->window, 0, NULL);
  ui->font = XLoadQueryFont(ui->display, "fixed");
  if (ui->font) XSetFont(ui->display, ui->gc, ui->font->fid);
  XMapRaised(ui->display, ui->window);
  XSync(ui->display, False);
  return 1;
}

static void ui_destroy(struct ui *ui) {
  if (!ui->display) return;
  XFreeGC(ui->display, ui->gc);
  if (ui->font) XFreeFont(ui->display, ui->font);
  XCloseDisplay(ui->display);
}

static void animate(struct ui *ui, int reverse) {
  unsigned long start = now_ms();
  for (;;) {
    unsigned long elapsed = now_ms() - start;
    double p = (double)elapsed / ANIM_MS;
    if (p >= 1.0) p = 1.0;
    draw(ui, reverse ? 1.0 - p : p, 0, 1);
    if (p >= 1.0) break;
    usleep(16000);
  }
}

static void blink(struct ui *ui) {
  for (int i = 0; i < 4; ++i) {
    draw(ui, 0, 1, (i % 2) == 0);
    usleep(BLINK_MS * 1000);
  }
}

static int handle_input(struct ui *ui, int fd, int request_fd, int *done) {
  char buf[256];
  ssize_t n = read(fd, buf, sizeof(buf));
  if (n <= 0) return 0;
  for (ssize_t i = 0; i < n; ++i) {
    unsigned char c = (unsigned char)buf[i];
    if (c == 3 || c == 27) {
      write_packet(request_fd, 'x', "");
      *done = 1;
      return 1;
    }
    if (c == '\r' || c == '\n') {
      if (ui->password_prompt || ui->prompt_type == PACKET_USERNAME) {
        ui->password_prompt = 0;
        ui->pending_type = ui->prompt_type == PACKET_USERNAME ? 'u' : 'p';
        memcpy(ui->pending_response, ui->input, ui->input_len + 1);
        ui->response_pending = 1;
        ui->response_due_ms = now_ms() + RESPONSE_PAUSE_MS;
        ui->prompt_type = 0;
        ui->message[0] = 0;
        wipe(ui->input, sizeof(ui->input));
        ui->input_len = 0;
      }
      continue;
    }
    if (c == 8 || c == 127) {
      if (ui->input_len) ui->input[--ui->input_len] = 0;
      continue;
    }
    if (c >= 32 && ui->input_len + 1 < sizeof(ui->input)) {
      ui->input[ui->input_len++] = (char)c;
      ui->input[ui->input_len] = 0;
    }
  }
  return 1;
}

int main(void) {
  struct ui ui = {0};
  if (!ui_init(&ui)) return 1;
  protect_secret_buffers(&ui);
  animate(&ui, 0);
  usleep(PROMPT_PAUSE_MS * 1000);

  int request_fd, response_fd;
  pid_t auth_pid;
  if (!start_authproto(&request_fd, &response_fd, &auth_pid)) {
    ui_destroy(&ui);
    return 1;
  }

  int done = 0;
  int status = 1;
  while (!done) {
    struct pollfd fds[2] = {{STDIN_FILENO, POLLIN, 0}, {request_fd, POLLIN, 0}};
    int polled = poll(fds, 2, 50);
    if (polled < 0 && errno == EINTR) continue;
    if (polled < 0) break;
    if (fds[0].revents & (POLLIN | POLLHUP)) handle_input(&ui, STDIN_FILENO, response_fd, &done);
    if (fds[1].revents & (POLLIN | POLLHUP)) {
      char type;
      char *message = NULL;
      int result = read_packet(request_fd, &type, &message);
      if (result <= 0) {
        int child_status;
        waitpid(auth_pid, &child_status, 0);
        if (WIFEXITED(child_status) && WEXITSTATUS(child_status) == 0) status = 0;
        done = 1;
      } else {
        if (type == PACKET_PASSWORD || type == PACKET_USERNAME) {
          ui.password_prompt = type == PACKET_PASSWORD;
          ui.prompt_type = type;
          ui.error = 0;
          strncpy(ui.message, message, sizeof(ui.message) - 1);
          ui.message[sizeof(ui.message) - 1] = 0;
        } else if (type == PACKET_ERROR || type == PACKET_INFO) {
          ui.error = type == PACKET_ERROR;
          strncpy(ui.message, message, sizeof(ui.message) - 1);
          ui.message[sizeof(ui.message) - 1] = 0;
        }
        wipe(message, strlen(message));
        free(message);
      }
    }
    if (ui.response_pending && now_ms() >= ui.response_due_ms) {
      write_packet(response_fd, ui.pending_type, ui.pending_response);
      wipe(ui.pending_response, sizeof(ui.pending_response));
      ui.response_pending = 0;
    }
    draw(&ui, 1, 0, 1);
  }
  close(request_fd);
  close(response_fd);
  if (!done) kill(auth_pid, SIGTERM);
  if (status == 0) {
    animate(&ui, 1);
    blink(&ui);
    usleep(FINAL_PAUSE_MS * 1000);
  }
  wipe(ui.input, sizeof(ui.input));
  wipe(ui.pending_response, sizeof(ui.pending_response));
  if (ui.secrets_locked) {
    munlock(ui.input, sizeof(ui.input));
    munlock(ui.pending_response, sizeof(ui.pending_response));
  }
  ui_destroy(&ui);
  return status;
}
