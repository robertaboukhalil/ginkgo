#/bin/bash

# -------------------------------------------------------------------------------------------------
# -- Simple admin view of all analyses currently running on the server
# -------------------------------------------------------------------------------------------------

n=0;
for SAMPLE in $(ps aux | grep "analyze.sh" | awk '{print $NF}' | sort | uniq);
do
  if [ -d uploads/$SAMPLE ]; then
    # Calculate step + percentage
    step=$(cat uploads/$SAMPLE/status.xml | grep step | tr -d '</step>')
    percent=$(cat uploads/$SAMPLE/status.xml | grep percentdone | tr -d '</percentdone>')
    ((n++))

    # Calculate progress
	progress=$(echo "scale=20;100*($step-1+$percent/100)/3" | bc | awk '{print int($1+0.5)}')
	# Plot progress bar
	progressBar=""
	for (( c=0; c<$progress; c++ )); do progressBar=$progressBar"|"; done
	for (( c=progress; c<100; c++ )); do progressBar=$progressBar"."; done

    #
    echo -e $n"\t"$SAMPLE"\t["$step"\t"$percent"%]\t"$progress"%\t"$progressBar;
  fi;
done | column -t

# Progress of current file uploads
echo -e "\n\n"
ls -lh /tmp/php* 2>/dev/null

