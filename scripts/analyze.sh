#!/bin/bash

# ==============================================================================
# == Launch analysis
# ==============================================================================

# ------------------------------------------------------------------------------
# -- Variables
# ------------------------------------------------------------------------------

home=/mnt/data/ginkgo
dir=${home}/uploads/${1}
source ${dir}/config
distMet=$distMeth
touch $dir/index.html

inFile=list
statFile=status.xml
genome=${home}/genomes/${chosen_genome}

if [ "$rmpseudoautosomal" == "1" ];
then
  genome=${genome}/pseudoautosomal
else
  genome=${genome}/original
fi

# ------------------------------------------------------------------------------
# -- Error Check and Reformat User Files
# ------------------------------------------------------------------------------

if [ "$f" == "0" ]; then
  touch ${dir}/ploidyDummy.txt
  facs=ploidyDummy.txt
else 
  # In case upload file with \r instead of \n (Mac, Windows)
  tr '\r' '\n' < ${dir}/${facs} > ${dir}/quickTemp
  mv ${dir}/quickTemp ${dir}/${facs}
  # 
  sed "s/.bed//g" ${dir}/${facs} | sort -k1,1 | awk '{print $1"\t"$2}' > ${dir}/quickTemp 
  mv ${dir}/quickTemp ${dir}/${facs}
fi

# ------------------------------------------------------------------------------
# -- Map Reads & Prepare Samples For Processing
# ------------------------------------------------------------------------------

total=`wc -l < ${dir}/${inFile}`

if [ "$init" == "1" ];
then

  # Clean directory
  rm -f ${dir}/*_mapped ${dir}/*.jpeg ${dir}/*.newick ${dir}/*.xml ${dir}/*.cnv ${dir}/Seg* ${dir}/results.txt

  # Map user bed files to appropriate bins
  cnt=0
  while read file;
  do
    ${home}/scripts/status ${dir}/${statFile} 1 $file $cnt $total

    # Unzip gzip files if necessary
    if [[ "${file}" =~ \.gz$ ]];
    then
      firstLineChr=$(zcat ${dir}/${file} | head -n 1 | cut -f1 | grep "chr")
      if [[ "${firstLineChr}" == "" ]];
      then
        awk '{print "chr"$0}' <(zcat ${dir}/${file}) > ${dir}/${file}_tmp
        mv ${dir}/${file}_tmp ${dir}/${file/.gz/}
        gzip -f ${dir}/${file/.gz/}
      fi
      ${home}/scripts/binUnsorted ${genome}/${binMeth} `wc -l < ${genome}/${binMeth}` <(zcat -cd ${dir}/${file}) `echo ${file} | awk -F ".bed" '{print $1}'` ${dir}/${file}_mapped

    # 
    else
      firstLineChr=$( head -n 1 ${dir}/${file} | cut -f1 | grep "chr")
      if [[ "${firstLineChr}" == "" ]];
      then
        awk '{print "chr"$0}' ${dir}/${file} > ${dir}/${file}_tmp
        mv ${dir}/${file}_tmp ${dir}/${file}
      fi
      ${home}/scripts/binUnsorted ${genome}/${binMeth} `wc -l < ${genome}/${binMeth}` ${dir}/${file} `echo ${file} | awk -F ".bed" '{print $1}'` ${dir}/${file}_mapped
      gzip ${dir}/${file}
    fi

    cnt=$(($cnt+1))
  done < ${dir}/${inFile}

  # Concatenate binned reads to central file  
  paste ${dir}/*_mapped > ${dir}/data
  rm -f ${dir}/*_mapped ${dir}/*_binned

fi

# ------------------------------------------------------------------------------
# -- Map User Provided Reference/Segmentation Sample
# ------------------------------------------------------------------------------

if [ "$segMeth" == "2" ]; then
    ${home}/scripts/binUnsorted ${genome}/${binMeth} `wc -l < ${genome}/${binMeth}` ${dir}/${ref} Reference ${dir}/${ref}_mapped
else
    ref=refDummy.bed
    touch ${dir}/${ref}_mapped
fi

# ------------------------------------------------------------------------------
# -- Run Mapped Data Through Primary Pipeline
# ------------------------------------------------------------------------------

if [ "$process" == "1" ]; then
  ${home}/scripts/process.R $genome $dir $statFile data $segMeth $binMeth $clustMeth $distMet $color ${ref}_mapped $f $facs $sex $rmbadbins
fi

# ------------------------------------------------------------------------------
# -- Recreate Clusters/Heat Maps (With New Parameters)
# ------------------------------------------------------------------------------

if [ "$fix" == "1" ]; then
  ${home}/scripts/reclust.R $genome $dir $statFile $binMeth $clustMeth $distMet $f $facs $sex
fi

# ------------------------------------------------------------------------------
# -- Create CNV profiles
# ------------------------------------------------------------------------------

nbCols=$(awk '{ print NF; exit; }' $dir/SegCopy)
for (( i=1; i<=$nbCols; i++ ));
do
  currCell=$(cut -f$i $dir/SegCopy | head -n 1 | tr -d '"')
  if [ "$currCell" == "" ]; then
    continue;
  fi
  cut -f$i $dir/SegCopy | tail -n+2 | awk '{if(NR==1) print "1,"$1; else print NR","prev"\n"NR","$1;prev=$1; }' > $dir/$currCell.cnv
done

# ------------------------------------------------------------------------------
# -- Call CNVs
# ------------------------------------------------------------------------------

${home}/scripts/CNVcaller ${dir}/SegCopy ${dir}/CNV1 ${dir}/CNV2

# ------------------------------------------------------------------------------
# -- Email notification of completion
# ------------------------------------------------------------------------------

if [ "$email" != "" ]; then
	echo -e "Your analysis on Ginkgo is complete! Check out your results at $permalink" | mail -s "Your Analysis Results" $email -- -F "Ginkgo"
fi
