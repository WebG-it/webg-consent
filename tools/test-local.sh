#!/usr/bin/env bash
# Joomla + MariaDB usa e getta per provare il plugin. Serve Docker acceso.
# Uso: JTAG=4.4-php8.2-apache N=wgc44 PORT=8744 bash tools/test-local.sh up | install | down
# Predefiniti: JTAG=6.2.0-php8.4-apache N=wgc PORT=8765. Le password di prova (usa e getta) stanno in .test-secrets.
set -e
export MSYS_NO_PATHCONV=1
cd "$(dirname "$0")/.."
JTAG="${JTAG:-6.2.0-php8.4-apache}"; N="${N:-wgc}"; PORT="${PORT:-8765}"
# password usa e getta generate una volta e tenute in .test-secrets (ignorato da git)
[ -f .test-secrets ] || python -c "import secrets;print('DBPW='+secrets.token_hex(8));print('ADMPW='+secrets.token_urlsafe(12)+'!9')" > .test-secrets
. ./.test-secrets
case "$1" in
up)
  docker network create $N-net >/dev/null 2>&1 || true
  docker run -d --name $N-db --network $N-net -e MARIADB_ROOT_PASSWORD=$DBPW -e MARIADB_DATABASE=j -e MARIADB_USER=j -e MARIADB_PASSWORD=$DBPW mariadb:11.4
  docker run -d --name $N-j --network $N-net -p 127.0.0.1:$PORT:80 -e JOOMLA_DB_HOST=$N-db -e JOOMLA_DB_USER=j -e JOOMLA_DB_PASSWORD=$DBPW -e JOOMLA_DB_NAME=j joomla:$JTAG
  echo "Aspetta che /var/www/html/installation/joomla.php esista, poi: bash tools/test-local.sh install"
  ;;
install)
  docker exec -u www-data $N-j php installation/joomla.php install --site-name="Test" --admin-user="Admin" --admin-username=admin --admin-password="$ADMPW" --admin-email=a@example.test --db-type=mysqli --db-host=$N-db --db-user=j --db-pass=$DBPW --db-name=j --db-prefix=wgc_ --db-encryption=0 --no-interaction
  python tools/build.py
  Z=$(ls dist/plg_system_webgconsent-*.zip | tail -1)
  docker cp "$Z" $N-j:/tmp/p.zip
  docker exec -u www-data $N-j php cli/joomla.php extension:install --path=/tmp/p.zip
  docker exec $N-db mariadb -uj -p$DBPW j -e "update wgc_extensions set enabled=1 where element='webgconsent'"
  echo "Prova su http://localhost:$PORT/ (admin: /administrator, utente admin)"
  ;;
down)
  docker rm -f $N-j $N-db; docker network rm $N-net
  ;;
*) echo "up | install | down"; exit 1;;
esac
