#!/bin/sh
# Gera o pacote para instalar em outro domínio só descompactando: qrcodes-<versão>.zip, na raiz do projeto.
# Leva o que está no último commit (nada de dados/, ferramentas/ nem arquivos do Docker; ver .gitattributes).
set -e
cd "$(dirname "$0")/.."
versao=$(sed -n "s/.*define('QR_VERSAO', '\(.*\)').*/\1/p" app/bootstrap.php)
git archive --format=zip -o "qrcodes-$versao.zip" HEAD
echo "Pacote criado: qrcodes-$versao.zip"
