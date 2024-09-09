#!/bin/bash
BASE_DIR=$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )
######################### INCLUSION LIB ##########################
#wget https://raw.githubusercontent.com/NebzHB/dependance.lib/master/dependance.lib --no-cache -O ${BASE_DIR}/dependance.lib &>/dev/null
PROGRESS_FILENAME="dependancy"
PLUGIN=$(basename "$(realpath ${BASE_DIR}/..)")
LANG_DEP=en
# TIMED=1
. ${BASE_DIR}/dependance.lib
##################################################################
#wget https://raw.githubusercontent.com/NebzHB/dependance.lib/master/pyenv.lib --no-cache -O ${BASE_DIR}/pyenv.lib &>/dev/null
. ${BASE_DIR}/pyenv.lib
TARGET_PYTHON_VERSION="3.11"
#VENV_DIR=${BASE_DIR}/venv
#APT_PACKAGES="python3-venv python3-pip ..."
##################################################################

launchInstall
