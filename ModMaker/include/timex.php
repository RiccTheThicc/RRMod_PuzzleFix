<?php

function IntervalToSeconds(\DateInterval $interval, bool $absolute = false) {
	$abs_int = $interval->days * 86400 + $interval->h * 3600 + $interval->i * 60 + $interval->s;
	if(!$absolute && $interval->invert){
		return -$abs_int;
	}
	return $abs_int;
}

function SmartDateIntervalFormat(DateInterval $diff){
	return $diff->format($diff->h == 0 ? ($diff->i == 0 ? "%s" : "%i:%S") : "%h:%I:%S");
}

function TssToDatetime(string $tss){
	return DateTime::createFromFormat("Y.m.d-H.i.s:v", $tss);
}

function DatetimeToTss(Datetime $dt){
	return $dt->format("Y.m.d-H.i.s:v");
}

function TimestampToTss(int $timestamp){
	$dt = new DateTime("@" . (string)$timestamp);
	//return $dt->format("Y.m.d-H.i.s:v");
	return DatetimeToTss($dt);
}

function GetTimeZoneOffsetString(){
	global $config;
	static $timeZoneOffsetString = null;
	
	if($timeZoneOffsetString === null){
		// Get local time in UTC... Which is what php always returns.
		$utc = new DateTimeZone("UTC");
		$utcDt = new DateTime("now", $utc);
		
		// Run a Windows-specific function to retrieve the ACTUAL local time, with proper timezone.
		$localTimeString = exec("time /T");
		$localDt12 = DateTime::createFromFormat("h:i A", $localTimeString, $utc);
		$localDt24 = DateTime::createFromFormat("H:i",   $localTimeString, $utc);
		if($localDt12 === FALSE && $localDt24 === FALSE){
			//printf("\nCouldn't figure out your time zone.\n\n");
			//printf(ColorStr("\nCouldn't figure out your time zone.\n\n", 255, 128, 128));
			//printf("Please go to config.txt and set your difference from UTC.\n\n");
			//printf("Example for UTC-03:00:\n");
			//printf("timezone_forced = \"-3 hours\"\n\n");
			//printf("Press Enter to exit or close the window.\n");
			//exit(1);
			printf("%s\n", ColorStr("Local timezone detection failed - falling back to UTC time", 192, 128, 128));
			$localDt24 = $utcDt;
		}
		
		$localDt = null;
		if($localDt12 !== FALSE){
			$localDt = $localDt12;
		}else{
			$localDt = $localDt24;
		}
		
		// Figure out the difference.
		$diff = $utcDt->diff($localDt);
		$minuteCount = $diff->h * 60 + $diff->i;
		$sign = ($diff->invert ? -1 : 1); // UTC+ or UTC-
		
		if($minuteCount > 12 * 60){
			// No clue if I'm doing this right I stg.
			$minuteCount -= abs(24 * 60);
			//$sign = -$sign;
		}
		
		// Ok now we know the time difference in minutes. We really should round it up nicely though.
		// Note that some timezones in the world have half-hour difference to UTC. Let's round up to 0.5 hours.
		$minuteCount = $sign * intval(round($minuteCount / 30)) * 30;
		//printf("Detected timezone offset: %s\n", ($minuteCount / 60) . " hours");
		$timeZoneOffsetString  = ($minuteCount . " minutes");
	}
	return $timeZoneOffsetString;
}

function TssToLocalDateTime($tss){
	$dt = TssToDatetime($tss);
	$timeZoneOffsetString = GetTimeZoneOffsetString();
	$localTimeZoneInterval = DateInterval::createFromDateString($timeZoneOffsetString);
	$dt->add($localTimeZoneInterval);
	return $dt;
}

function GetLocalDt(){
	// TODO please rewrite this properly huh
	return TssToLocalDateTime(DatetimeToTss(new DateTime("now", new DateTimeZone("UTC"))));
}

