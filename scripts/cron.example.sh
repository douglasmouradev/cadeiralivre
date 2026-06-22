#!/usr/bin/env bash
# Exemplo de crontab (ajuste o caminho):
# */5 * * * * cd /www/wwwroot/cadeiralivre.tdesksolutions.com.br && php scripts/process_mail_queue.php >> storage/logs/mail-queue.log 2>&1
# 0 * * * * cd /www/wwwroot/cadeiralivre.tdesksolutions.com.br && php scripts/send_appointment_reminders.php >> storage/logs/reminders.log 2>&1
# 0 3 * * * cd /www/wwwroot/cadeiralivre.tdesksolutions.com.br && ./scripts/backup_mysql.sh >> storage/logs/backup.log 2>&1
