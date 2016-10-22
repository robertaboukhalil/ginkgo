workflow ginkgo {
    String dir_input
    String genome
    String binning
    String clustdist
    String clustlinkage
    String segmentation
    String ref
    String color
    String facs
    String cells
    Boolean maskbadbins
    Boolean masksexchrs
    Boolean maskpsrs

    call ginkgo_run {
        input:
            dir_input = dir_input,
            genome = genome,
            binning = binning,
            clustdist = clustdist,
            clustlinkage = clustlinkage,
            segmentation = segmentation,
            ref = ref,
            color = color,
            facs = facs,
            cells = cells,
            maskbadbins = maskbadbins,
            masksexchrs = masksexchrs,
            maskpsrs = maskpsrs
    }
}

task ginkgo_run {
    String dir_input
    String genome
    String binning
    String clustdist
    String clustlinkage
    String segmentation
    String ref
    String color
    String facs
    String cells
    Boolean maskbadbins
    Boolean masksexchrs
    Boolean maskpsrs

    command <<<
        /ginkgo/cli/ginkgo.sh \
          --input ${dir_input} \
          --genome ${genome} \
          --binning ${binning} \
          --clustdist ${clustdist} \
          --clustlinkage ${clustlinkage} \
          --segmentation ${segmentation} \
          --ref ${ref} \
          --color ${color} \
          --facs ${facs} \
          --cells ${cells} \
          --maskbadbins ${maskbadbins} \
          --masksexchrs ${masksexchrs} \
          --maskpsrs ${maskpsrs}

    >>>

  output {
    File out = "${dir_input}/archive.tar"
  }

  runtime {
     docker: "robertaboukhalil/ginkgo"
     disks: "local-disk 200 SSD"
     cpu: "32"
  }

}
