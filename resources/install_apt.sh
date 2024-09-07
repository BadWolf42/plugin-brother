#!/bin/bash
BASEDIR=$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )
######################### INCLUSION LIB ##########################
#wget https://raw.githubusercontent.com/NebzHB/dependance.lib/master/dependance.lib --no-cache -O $BASEDIR/dependance.lib &>/dev/null
PROGRESS_FILENAME=$1
PLUGIN=$(basename "$(realpath $BASEDIR/..)")
LANG_DEP=en
TIMED=1
. ${BASEDIR}/dependance.lib
##################################################################
#wget https://raw.githubusercontent.com/NebzHB/dependance.lib/master/pyenv.lib --no-cache -O ${BASEDIR}/pyenv.lib &>/dev/null
. ${BASEDIR}/pyenv.lib
TARGET_PYTHON_VERSION="3.11"
#VENV_DIR=${BASEDIR}/venv
#APT_PACKAGES="python3-venv python3-pip ..."
##################################################################

launchInstall
