#!/bin/bash

if [ `whoami` != "root" ]
then
  echo "Must run as root: sudo bash"
  exit
else
  echo "Confirmed running as root"
fi

if [ -n "$STY" ]; then
  echo "Confirmed running inside of GNU screen."
else
  echo "Not running inside of GNU screen."
  exit
fi

cd /local1/work/ginkgo-uploads

if [ ! -r ~/list.clean ]
then
  echo "generating ~/list.full"
  ls -ltrs | grep -v '\->' > ~/list.full
  cat << EOF
Prune the list.full like this:
cat ~/list.full | awk '{if (\$7=="Nov"){print \$10}}' > ~/list.clean
EOF
  exit
fi

if [ $# -ne 1 ]
then
  echo "USAGE: clean_ginkgo.sh dirprefix"
  exit
fi

dirprefix=$1
dest=/local2/work/ginkgo-uploads

echo "moving files in ~/list.clean to $dest/$dirprefix/"

mkdir -p $dest/$dirprefix
for i in `cat ~/list.clean`; do echo $i; mv $i $dest/$dirprefix/; ln -s $dest/$dirprefix/$i $i; done
mv ~/list.clean ~/list.clean.done
