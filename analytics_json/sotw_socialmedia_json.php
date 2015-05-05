<?php

	//
	// This grabs social media information from the services that CMNH uses, HootSuite and Pinterest
	// It has been specifically tailored for CMNH and will *probably* work for other HootSuite & Pinterest accounts with slight modificaions
	// It only supports the Twitter Profile Overview and Facebook Page Overview reports and the Pinterest Re-pin report
	// NOTE:: Neither services has an API for analytics data, so this is essentially screen scraping and will most likely fail at the whims of HootSuite & Pinterest
 
 	//
 	// The sotw_analytics_v2.js is looking for JSON-data formatted thusly:
	// (Note: the report can support multiple metrics per platform, CMNH just does not currently collect any.) 
	 	
 	/*
 	
	 {
		"accounts": [
			{
				"title": "CarnegieMNH",
				"platforms": [
					{
						"platform": "Facebook",
						"metrics": [
					
							{
							 "metric": "Likes",
							 "value": 15650,
							 "change": 672
							 }
						]
					},
					{
						"platform": "Twitter",
						 "metrics": [
					
							{
							 "metric": "Followers",
							 "value": 3381,
							 "change": 90
							 }
						]
					}
				]
			},
			{
				"title": "Dippy the Dino",
				"platforms": [
					{
						"platform": "Facebook",
						"metrics": [
					
							{
							 "metric": "Likes",
							 "value": 795,
							 "change": 115
							 }
						]
					},
					{
						"platform": "Twitter",
						 "metrics": [
					
							{
							 "metric": "Followers",
							 "value": 1154,
							 "change": 26
							 }
						]
					}
				]
			},
			{
				"title": "Powdermill Nature Reserve",
				"platforms": [
					{
						"platform": "Facebook",
						"metrics": [
					
							{
							 "metric": "Likes",
							 "value": 804,
							 "change": 29
							 }
						]
					}
				]
			}
		]
	}
 	
 	*/
  
	error_reporting(E_ALL & E_NOTICE);

	// Running the server on a different domain or from your local machine?
	// You will need to uncomment or adjust this 
	//
	//header('Access-Control-Allow-Origin: *'); 
	
	$month=$_GET['month'];
	$year=$_GET['year'];
	
	if (!$month || !$year) {
	  
	  $month=date("m")-1;
	  $year=date("Y");
	  if ($month<0) {$month=11; $year--;}
	
	  $p_month=$month-1;
	  $p_year=$year;
	  if ($p_month<0) {$p_month=11; $p_year--;}
	  
	}
	
	//
	// These are the CMNH accounts. Adjust accordingly. report_id can be found in the URL when the desired analytics report is selected at HootSuite
	
	$socialmedia_accounts='[ 
		{ "title": "CarnegieMNH",
		  "type": "Facebook",
		  "report_id": 1909855 },
		{ "title": "Dippy the Dino",
		  "type": "Facebook",
		  "report_id": 1909874 },
		{ "title": "Powdermill Nature Reserve",
		  "type": "Facebook",
		  "report_id": 1909877 },
		{ "title": "Dippy the Dino",
		  "type": "Twitter",
		  "report_id": 1909882 },
		{ "title": "CarnegieMNH",
		  "type": "Twitter",
		  "report_id": 1909878 },
		{ "title": "CarnegieMNH",
		  "type": "Pinterest",
		  "domain": "carnegiemnh.org"
		}
	]';
	
	$hs_accounts=json_decode($socialmedia_accounts);
	
	$startdate=date("Y-m-d", strtotime($year."-".$month."-01"));
	$enddate=date("Y-m-t", strtotime($startdate));
	$p_startdate=date("Y-m-d", strtotime($p_year."-".$p_month."-01"));
	$p_enddate=date("Y-m-t", strtotime($p_startdate));
	
	$hs_date1=date("M t, Y", strtotime($p_startdate));
	$hs_date2=date("M d, Y", strtotime($enddate));
	
	//
	// Supply URI-encoded credentials
	
	$hs=new HootSuite(' --username-- ',' --password-- ');
	$pin=new Pinterest(' --username-- ',' --password-- ');
	
	$social_media="";
	$social_media['accounts']=Array();
	$a=Array();
	
	foreach ($hs_accounts as $ac) {
	
		if ($ac->type=='Twitter') {
		
			$results=$hs->GetReport($ac->report_id,$hs_date1, $hs_date2);
			
			//
			// Find last month/this month tweet followers
			
			foreach($results as $r) {
				
				if ($r[0]==$p_enddate) {
					
					$last_month=$r[1];
				
				} else if ( $r[0]==$enddate ) {
					
					$this_month=$r[1];
					
				}
				
			}
			
		} else if ($ac->type=='Facebook') {
			
			$results=$hs->GetReport($ac->report_id,$hs_date1, $hs_date2);

			//
			// Find last month/this month Likes
			
			for ($r=0; $r<count($results); $r++) {
				
				if ($results[$r][0]=='Total Likes') {
					
					$this_month=$results[$r+1][0];
					$last_month=$results[$r+1][0]-$results[$r+1][2];
					break;
				}
			
			}
			
		} else if ($ac->type=='Pinterest') {
			
			$results=$pin->GetReport($ac->domain,$startdate, $enddate);

			$last_month=0;
			$this_month=0;

			for ($r=2; $r<count($results); $r++) {
			
				if ($results[$r][0]=="") {
					
					break;
					
				} else {
				
					if (strtotime($results[$r][0])<strtotime($startdate)) {
						
						$last_month+=intval($results[$r][1]);
						
					}
					
					$this_month+=intval($results[$r][1]);
				
				}

			}

		}
		
		
		$m="";
		$p="";
		
		if ($ac->type=='Facebook') {
			$m['metric']='Likes';
		} else if ($ac->type=='Twitter') {
			$m['metric']='Followers';
		} else if ($ac->type=='Pinterest') {
			$m['metric']='Repins';
		}

		$m['value']=intval($this_month);
		$m['change']=intval($this_month)-intval($last_month);
		
		$p['platform']=$ac->type;
		$p['metrics']=Array();
		array_push($p['metrics'],$m);
		
		if (!$a[$ac->title]) {
		
			$a[$ac->title]=Array();
			$a[$ac->title]['platforms']=Array();
		}
		
		$a[$ac->title]['title']=$ac->title;
		array_push($a[$ac->title]['platforms'],$p);
		
	}

	foreach ($a as $aa) {
		array_push($social_media['accounts'], $aa);
	}

	print json_encode($social_media);
			
	
class HootSuite {
	
	private $cookies;
	
	public function HootSuite($username, $password) {

		$loginUrl = 'https://hootsuite.com/login';
	
		$header = array(
			'Origin: https://hootsuite.com',
			'Accept-Encoding: gzip, deflate',
			'Accept-Language: en-US,en;q=0.8',
			'User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_9_5) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/42.0.2311.90 Safari/537.36',
			'Content-Type: application/x-www-form-urlencoded',
			'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
			'Cache-Control: max-age=0',
			'Referer: https://hootsuite.com/login',
			'Connection: keep-alive'
			);
	
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $loginUrl);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_HEADER, 1);
		curl_setopt($ch, CURLINFO_HEADER_OUT, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, 'email='.$username.'&password='.$password.'&googleAuthenticator=&method=email');
		curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		
		//
		// Retrieve authentication cookies
	
		$data        = curl_exec($ch);
		$header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
		$header      = substr($data, 0, $header_size);
	
		preg_match_all("/^Set-Cookie: (.*?);/ism", $header, $cookies);
		foreach( $cookies[1] as $cookie ){
			$buffer_explode = strpos($cookie, "=");
			$this->cookies[ substr($cookie,0,$buffer_explode) ] = substr($cookie,$buffer_explode+1);
		}
		
	}
	
	public function GetReport($report_id, $startdate, $enddate) {	
	
		if( count($this->cookies) > 0 ){

			$ch = curl_init();

			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_HEADER, 0);
			curl_setopt($ch, CURLINFO_HEADER_OUT, 0);
			curl_setopt($ch, CURLOPT_POST, 0);
		
			$cookieBuffer = array();
			foreach(  $this->cookies as $k=>$c ) $cookieBuffer[] = "$k=$c";
			curl_setopt($ch, CURLOPT_COOKIE, implode("; ",$cookieBuffer) );
		
			curl_setopt($ch, CURLOPT_URL, 'https://hootsuite.com/analytics/view?reportId='.$report_id.'&displayType=CSV&dateRange='.$startdate.'%20-%20'.$enddate);
			$content = curl_exec($ch);
			
			curl_close($ch);
		
			return parse_csv( $content );
			
		}
			
	}

}


class Pinterest {
	
	private $cookies;
	
	public function Pinterest($username, $password) {

		$loginUrl = 'https://www.pinterest.com/resource/UserSessionResource/create/';
	
		//
		// I am sure these headers need to be constructed with more care
		// But OMG it was easier to just copy the headers from a working transaction
		// They may not work for you (pay attention to the CSRTokens)
	
		$header = array(
			'Origin: https://www.pinterest.com',
			'Accept-Encoding: gzip, deflate',
			'Accept-Language: en-US,en;q=0.8',
			'X-Requested-With: XMLHttpRequest',
			'X-NEW-APP: 1',
			'X-APP-VERSION: cc98082',
			'X-CSRFToken: 6m5X9Bq08FJPzuDsQVHEYZ535jrobzjP',
			'Cookie: _pinterest_pfob=disabled; _b="AQYwV7hlDSFFyLMPdUhyEN2CYbgEZy7K+gZFGKeiv1+gr+IR7Fx/rIfBVYi5ka6jRHM="; _pinterest_cm=disabled; _pinterest_referrer="https://www.google.com/"; __utmt=1; csrftoken=6m5X9Bq08FJPzuDsQVHEYZ535jrobzjP; logged_out=True; fba=True; _pinterest_sess=TWc9PSZMdTB3Uzl1SW9pamxCTGtienRIclBVbEpMZDhVZ1k3aGZ4Q0ZSSnZNZWFPa0c1WjJZRWFTdDhkM0VSbm9UV3NPUUY0QnhsTWRGdEpLNGJ1VHFrZVcyVlFNSWVFM3NibHJONFp6WXR4QzVTMklPV3BEaXNVOTZHZldpemNPY1JTeWNFdER2dzMwcFhpc3ducmgrcUhOa0E9PSZJdFlVeGhvRy9mU3FERmJLc2ZWT0NrSmhMUkk9; c_dpr=1; __utma=229774877.899339231.1401714023.1430512681.1430833335.27; __utmb=229774877.7.9.1430833344507; __utmc=229774877; __utmz=229774877.1430833335.27.15.utmcsr=google|utmccn=(organic)|utmcmd=organic|utmctr=(not%20provided); GCSCE_5B243246522C4B23F685F2EB9D5F3C78DF8A0272_S3=C=694505692171-31closf3bcmlt59aeulg2j81ej68j6hk.apps.googleusercontent.com:S=4adb5b3e2d7a9d76a389f4f5b105b96534ff6dd1.dZU3N5SjTIz1Z3w3.1893:I=1430833375:X=1430919775',
			'X-Pinterest-AppState: active', 			
			'User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_9_5) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/42.0.2311.90 Safari/537.36',
			'Content-Type:  application/x-www-form-urlencoded; charset=UTF-8',
			'Accept: application/json, text/javascript, */*; q=0.01' ,
			'Cache-Control: max-age=0',
			'Referer: https://www.pinterest.com/',
			'Connection: keep-alive'
			);
	
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $loginUrl);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_HEADER, 1);
		curl_setopt($ch, CURLINFO_HEADER_OUT, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, 'source_url=%2Flogin%2F&data=%7B%22options%22%3A%7B%22username_or_email%22%3A%22'.$username.'%22%2C%22password%22%3A%22'.$password.'%22%7D%2C%22context%22%3A%7B%7D%7D&module_path=App()%3ELoginPage()%3ELogin()%3EButton(text%3DLog+In%2C+size%3Dlarge%2C+class_name%3Dprimary%2C+type%3Dsubmit)');
		curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		
		//
		// Retrieve authentication cookies
	
		$data        = curl_exec($ch);
		$header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
		$header      = substr($data, 0, $header_size);
	
		preg_match_all("/^Set-Cookie: (.*?);/ism", $header, $cookies);
		foreach( $cookies[1] as $cookie ){
			$buffer_explode = strpos($cookie, "=");
			$this->cookies[ substr($cookie,0,$buffer_explode) ] = substr($cookie,$buffer_explode+1);
		}
		
	}
	
	public function GetReport($domain, $startdate, $enddate) {	
	
		if( count($this->cookies) > 0 ){

			$ch = curl_init();

			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_HEADER, 0);
			curl_setopt($ch, CURLINFO_HEADER_OUT, 0);
			curl_setopt($ch, CURLOPT_POST, 0);
			//curl_setopt($ch, CURLOPT_HTTPGET, true);
			//curl_setopt($ch, CURLOPT_ENCODING , "");
			
			$cookieBuffer = array();
			foreach(  $this->cookies as $k=>$c ) $cookieBuffer[] = "$k=$c";
			curl_setopt($ch, CURLOPT_COOKIE, implode("; ",$cookieBuffer) );

			//
			// There is no way to get current a current repin value, so I slurp what I assume is repins from when the account was created? Maybe you need to adjust the start_date?
		
			curl_setopt($ch, CURLOPT_URL, 'https://analytics.pinterest.com/analytics/domain/'.$domain.'/export/?application=all&tab=repins&end_date='.$enddate.'&start_date=2012-11-01');
			
			$content = curl_exec($ch);

			curl_close($ch);
		
			return parse_csv( $content );
			
		}
			
	}

}



function parse_csv ($csv_string, $delimiter = ",", $skip_empty_lines = true, $trim_fields = true)
{
    $enc = preg_replace('/(?<!")""/', '!!Q!!', $csv_string);
    $enc = preg_replace_callback(
        '/"(.*?)"/s',
        function ($field) {
            return urlencode(utf8_encode($field[1]));
        },
        $enc
    );
    $lines = preg_split($skip_empty_lines ? ($trim_fields ? '/( *\R)+/s' : '/\R+/s') : '/\R/s', $enc);
    return array_map(
        function ($line) use ($delimiter, $trim_fields) {
            $fields = $trim_fields ? array_map('trim', explode($delimiter, $line)) : explode($delimiter, $line);
            return array_map(
                function ($field) {
                    return str_replace('!!Q!!', '"', utf8_decode(urldecode($field)));
                },
                $fields
            );
        },
        $lines
    );
}


?>
