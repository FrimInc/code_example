FROM php:7.4-fpm

RUN apt-get update && apt-get -y install cron
COPY ./cronjobs /etc/cron.d/cronjobs
RUN chmod 0644 /etc/cron.d/cronjobs
RUN crontab /etc/cron.d/cronjobs
RUN touch /var/log/cron.log
CMD cron && tail -f /var/log/cron.log