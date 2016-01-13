#!/usr/bin/perl

# LSL HTTP server throughput benchmark
# Jeff Kelley, 2015

use strict;
use URI::URL;
use Net::HTTP;
use Time::HiRes qw ( time sleep  );

$SIG{PIPE} = 'IGNORE';

# Paste here the url allocated by llRequestURL()
my $objectUri = "http://grid.pescadoo.net:9000/lslhttp/d75dcce1-cec7-41fd-9260-c524c9a1d4af/";


my $http; # Net::HTTP object

sub HttpOpen {
	my $url = shift;

	my $urlo = new URI::URL $url;
	my $host = $urlo->netloc;

	$http = Net::HTTP->new (KeepAlive => 1, Host => $host) || die $@;
}

sub HttpRequest {
	my ($url,$method,$req) = @_;

	my $urlo = new URI::URL $url;
	my $path = $urlo->path;

	# Re-open the connection 
	# in case of broken pipe

	my $conn = $http->connected;
	if (!defined $conn) {
		printf "Broken pipe\n";
		HttpOpen ($url);
	}

	my $ok = $http->write_request(GET => "$path?$req")	if $method eq 'GET';
	my $ok = $http->write_request(POST => $path, $req)	if $method eq 'POST';

 	return $ok;
}



my $reqSec = 200; # Requests/second
my $period = 1.0/$reqSec;
my $seqnum;


 
HttpOpen ($objectUri);

while(1) {
	$seqnum++;

	my $time1 = Time::HiRes::time;
	my $ok = HttpRequest ($objectUri, 'POST', "seq=$seqnum");
	my $time2 = Time::HiRes::time;

	my $elapsed = $time2 - $time1;		# Time elapsed for the request
	my $waiting = $period - $elapsed;	# Time to wait to next request

	printf "(%d) http=%f wait=%f total=%f\n", $ok, $elapsed, $waiting, $elapsed + $waiting;

	Time::HiRes::sleep $waiting unless $waiting < 0;
}