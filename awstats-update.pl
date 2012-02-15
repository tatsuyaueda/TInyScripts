#!/usr/local/bin/perl

#---
# AWStats Update Script
#  Tatsuya Ueda
#---
# Config File: awstats.HOSTNAME.conf
# Output Dir: HTDOCS/stats/
#---

@conf = (
  { conf => "www.s-lines.net",		htdocs => "/home/www/htdocs"		},
  { conf => "www2.s-lines.net", 	htdocs => "/home/www/htdocs2"		},
	);

$awstats = "/usr/local/AWStats/awstats.pl";
$awstats_build = "/usr/local/AWStats/awstats_buildstaticpages.pl";
$chown = "/usr/sbin/chown";

foreach my $i (@conf){
	my $conf   = $i->{conf};
	my $htdocs = $i->{htdocs};

	if($conf eq "clamav.s-lines.net"){
		$htdocs = "$htdocs/local_stats";
	}else{
		$htdocs = "$htdocs/stats";
	}

	# Update
	system("$awstats config=$conf -update 1> /dev/null");
	system("$awstats_build -config=$conf -staticlinks -dir=$htdocs -awstatsprog=$awstats 1> /dev/null");
	system("$chown nobody:nobody $htdocs/*.html");
}