#!/usr/bin/perl -w
use strict;

my $TAG="CB:Z:";

while (<>)
{
  my @fields = split /\s+/;

  my $chr  = $fields[2];
  my $s    = $fields[3];
  my $e    = $s + length($fields[9]);
  my $name = $fields[0];
  my $mq   = $fields[4];
  my $dir  = "+";

  my $tag = undef;

  for (my $idx = 11; $idx < scalar @fields; $idx++)
  {
    if (substr($fields[$idx], 0, length($TAG)) eq $TAG)
    {
      my $tag = substr($fields[$idx], length($TAG));

      print "$chr\t$s\t$e\t$name\t$mq\t$dir\t$tag\n";
      last;
    }
  }
}
