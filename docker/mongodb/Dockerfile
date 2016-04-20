FROM mongo:3
MAINTAINER Vladimír Kriška <vlado@keboola.com>
ENV DEBIAN_FRONTEND noninteractive

RUN apt-get update -q \
  && apt-get install openssh-server -y

RUN mkdir -p /root/.ssh/ \
  && echo 'ssh-rsa AAAAB3NzaC1yc2EAAAADAQABAAABAQCvzAADEmS+fqf2YoClcSQJOAhkS5O5AFV18vLpMb8gVuI7Hjb/XPhsK9uX3mkTTRc6DlvCrl9gfILQ53YrmfmzEIq8FWW4i+R8ZaI4gchh4+QYuvMO7Q2Rgz+WhZsknGLQy2TJ5TkHvtZwagaUxYYmOFvZpwQwWIsQysL1jCYbGknLKJR39WM8rhs5Yk4Y3cMtLw4KGQ35WsFGrSrLuxajlnB8Ob+uMWvMwa8QRjE3adw3rZnjYIgzWiToQU9rDPkAZndUvPUDRJcCqnZw5iceDhPXtOv2b0W+bwrT3xxQVVTTVBnNF9om11hfitpSvJ2YBgTdLr7tvjh+RdW3Zl+t root@6eb3e87c2533' > /root/.ssh/authorized_keys \
  && chown root:root /root/.ssh/authorized_keys \
  && chmod 600 /root/.ssh/authorized_keys

CMD sh -c 'service ssh start && mongod'
