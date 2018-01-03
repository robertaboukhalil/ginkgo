#!/usr/bin/perl -w
use strict;
use FileHandle;

my $USAGE = "splitbarcodes.pl validbarcodes all.bed directory >& split.log\n";

my $validfile = shift @ARGV or die $USAGE;
my $allfile   = shift @ARGV or die $USAGE;
my $outdir    = shift @ARGV or die $USAGE;

my %valid;
my %invalid;
my $invalidfh = FileHandle->new("> $outdir/invalid.bed");

open VALID, "$validfile" or die "Cant open $validfile ($!)\n";

my $validbarcodescnt = 0;
while (<VALID>)
{
  chomp;
  my $cellid = $_;
  $valid{$cellid}->{cnt} = 0;
  $valid{$cellid}->{fh} = FileHandle->new("> $outdir/$cellid.bed");
  $validbarcodescnt++;
}

print STDERR "= Indexed $validbarcodescnt valid barcodes\n";


open BED, "$allfile" or die "Cant open $allfile ($!)\n";

my $validaln = 0;
my $invalidaln = 0;
my $allaln = 0;

while (<BED>)
{
  my @fields = split /\s+/, $_;
  my $cellid = $fields[6];

  if (exists $valid{$cellid})
  {
    $validaln++;
    $valid{$cellid}->{cnt}++;
    my $fh = $valid{$cellid}->{fh};
    print $fh "$fields[0]\t$fields[1]\t$fields[2]\t$fields[3]\t$fields[4]\t$fields[5]\n";
  }
  else
  {
    $invalidaln++;
    print $invalidfh $_;
    $invalid{$cellid}->{cnt}++;
  }

  $allaln++;

  if (($allaln % 1000000) == 0)
  {
     print STDERR "= Processed $validaln valid and $invalidaln invalid aligments ($allaln total)\n";
  }
}

print STDERR "= Processed $validaln valid and $invalidaln invalid aligments ($allaln total)\n";

foreach my $cellid (sort keys %valid)
{
  my $aln = $valid{$cellid}->{cnt};
  print STDERR "+ $cellid\t$aln\n";
}

foreach my $cellid (sort keys %invalid)
{
  my $aln = $valid{$cellid}->{cnt};
  print STDERR "- $cellid\t$aln\n";
}
