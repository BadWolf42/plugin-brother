#!/bin/bash
######################### INCLUSION LIB ##########################
BASEDIR=$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )
#wget https://raw.githubusercontent.com/NebzHB/dependance.lib/master/dependance.lib -O $BASEDIR/dependance.lib &>/dev/null
PROGRESS_FILENAME=dependancy
PLUGIN=$(basename "$(realpath $BASEDIR/..)")
LANG_DEP=en
. ${BASEDIR}/dependance.lib
##################################################################

pre
step 0 "Synchronize the package index"
try sudo apt-get update

step 10 "Purge dynamic contents"
silent rm -rf $BASEDIR/venv
silent rm -rf $BASEDIR/__pycache__

step 30 "Install python3 venv and pip debian packages"
try sudo DEBIAN_FRONTEND=noninteractive apt-get install -y python3-venv python3-pip

step 50 "Create a python3 Virtual Environment"
try sudo -u www-data python3 -m venv $BASEDIR/venv

step 70 "Install required python3 libraries in venv"
try sudo -u www-data $BASEDIR/venv/bin/pip3 install --no-cache-dir -r $BASEDIR/requirements.txt

post
