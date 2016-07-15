<?php

	//
	// This grabs social media information from the services that CMNH uses, HootSuite, Pinterest and Tumblr
	// It has been specifically tailored for CMNH and will *probably* work for other HootSuite & Pinterest accounts with slight modifications
	// It only supports the Twitter Profile Overview and Facebook Page Overview reports and the Pinterest Re-pin report
	// NOTE:: Neither services has an API for analytics data, so this is essentially screen scraping and will most likely fail at the whims of HootSuite & Pinterest
	//
	// Tumblr uses OAUTH2 authentication and requires the user to generate a consumer key via their website 
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
	header('Access-Control-Allow-Origin: *'); 
	
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
	
	$hootsuite_accounts='[ 
		{ "title": "CarnegieMNH",
		  "type": "Facebook",
		  "report_id": 3525432 },
		{ "title": "Dippy the Dino",
		  "type": "Facebook",
		  "report_id": 3525433 },
		{ "title": "PNR",
		  "type": "Facebook",
		  "report_id": 3525435 },
		{ "title": "CarnegieMNH",
		  "type": "Twitter",
		  "report_id": 3525429 },
		{ "title": "CarnegieMNH",
		  "type": "Pinterest",
		  "domain": "carnegiemnh.org"
		},
		{ "title": "CarnegieMNH",
		  "type": "Tumblr"
		}
	]';
	
	$hs_accounts=json_decode($hootsuite_accounts);
	
	$startdate=date("Y-m-d", strtotime($year."-".$month."-01"));
	$enddate=date("Y-m-t", strtotime($startdate));
	$p_startdate=date("Y-m-d", strtotime($p_year."-".$p_month."-01"));
	$p_enddate=date("Y-m-t", strtotime($p_startdate));
	
	$hs_date1=date("M t, Y", strtotime($p_startdate));
	$hs_date2=date("M d, Y", strtotime($enddate));
	
	$hs=new HootSuite(' --username-- ',' --password-- ');
	$pin=new Pinterest(' --username-- ',' --password-- ');
	
	#
	# Now with Tumblr support (yay.)
	
	$tum=new Tumblr(' -- tumblr url -- ',' -- consumer key -- ');

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

		} else if ($ac->type=='Tumblr') {
			
			$results=$tum->GetReport($startdate, $enddate);

			$this_month=$results['last_month']+$results['this_month'];
			$last_month=$results['last_month'];
	
		}
		
		$m="";
		$p="";
		
		if ($ac->type=='Facebook') {
			$m['metric']='Likes';
		} else if ($ac->type=='Twitter') {
			$m['metric']='Followers';
		} else if ($ac->type=='Pinterest') {
			$m['metric']='Repins';
		} else if ($ac->type=='Tumblr') {
			$m['metric']='Notes';
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

		$header_out = array(
			'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
			'Accept-Language: en-US,en;q=0.8',
			'Cache-Control: max-age=0',
			'Connection: keep-alive',
			'Referer: https://hootsuite.com',
			'User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_9_5) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/45.0.2454.85 Safari/537.36'
			);

		$loginUrl = 'https://hootsuite.com/login';
		
		//
		// Login redirect to capture csrfToken (not needed?)
		
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $loginUrl);
		curl_setopt($ch, CURLOPT_POST, 0);
		curl_setopt($ch, CURLOPT_HEADER, 1);
		curl_setopt($ch, CURLINFO_HEADER_OUT, 1);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $header_out);
		curl_setopt($ch, CURLOPT_POSTFIELDS, 0);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_COOKIEFILE, "");    // OMG. You need this when curl does redirects to capture cookies correctly.
		
		$data        = curl_exec($ch);
		$header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
		$header      = substr($data, 0, $header_size);

		preg_match_all("/^Set-Cookie: (.*?);/ism", $header, $cookies);
		foreach( $cookies[1] as $cookie ){
			$buffer_explode = strpos($cookie, "=");
			$this->cookies[ substr($cookie,0,$buffer_explode) ] = substr($cookie,$buffer_explode+1);
		}

		preg_match("/^.*?csrfToken = '(.*?)';/m", $data, $csrf_token);
		preg_match("/Location: (\/login?.*)\r/i", $header, $where);

		$information = curl_getinfo($ch);

		curl_close($ch);

		//
		// Login and verify credentials

		$post_data=array('email'=>$username,
						 'password'=>$password,
						 'googleAuthenticator'=>'',
						 'method'=>'email',
						 'csrfToken'=>$csrf_token[1]);

		$referer="https://hootsuite.com".$where[1];

		$header_out = array(
			'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
			'Accept-Encoding: gzip, deflate',
			'Accept-Language: en-US,en;q=0.8',
			'Cache-Control: max-age=0',
			'Connection: keep-alive',
			'Content-Type: application/x-www-form-urlencoded',
			'Host: hootsuite.com',
			'Origin: https://hootsuite.com',
			'Referer: '.$referer,
			'Upgrade-Insecure-Requests: 1',
			'User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_9_5) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/45.0.2454.85 Safari/537.36'
			);

		$ch= curl_init();
		curl_setopt($ch, CURLOPT_URL, "https://hootsuite.com/login");
		curl_setopt($ch, CURLOPT_HEADER, 1);
		curl_setopt($ch, CURLINFO_HEADER_OUT, 1);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $header_out);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_data));
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

		$cookieBuffer = array();
		foreach(  $this->cookies as $k=>$c ) $cookieBuffer[] = "$k=$c";
		curl_setopt($ch, CURLOPT_COOKIE, implode("; ",$cookieBuffer) );
		
		$data        = curl_exec($ch);
		$header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
		$header="";
		$header      = substr($data, 0, $header_size);

		preg_match_all("/^Set-Cookie: (.*?);/ism", $header, $cookies);
		foreach( $cookies[1] as $cookie ){
			$buffer_explode = strpos($cookie, "=");
			$this->cookies[ substr($cookie,0,$buffer_explode) ] = substr($cookie,$buffer_explode+1);
		}

		$information = curl_getinfo($ch);
		curl_close($ch);
		preg_match("/Location: (.*)\r/i", $header, $ww);
		$where="https://hootsuite.com".$ww[1];

		//
		// authorize for oauth2
		
		$header_out = array(
			'Accept-Encoding: gzip, deflate, sdch',
			'Accept-Language: en-US,en;q=0.8',
			'User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_9_5) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/45.0.2454.85 Safari/537.36',
			'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
			'Cache-Control: max-age=0',
			'Connection: keep-alive',
			'Upgrade-Insecure-Requests: 1',
			'Host: hootsuite.com',
			'Referer: '.$referer
			);

		$ch= curl_init();
		curl_setopt($ch, CURLOPT_URL, $where);
		curl_setopt($ch, CURLOPT_HEADER, 1);
		curl_setopt($ch, CURLINFO_HEADER_OUT, 1);
		curl_setopt($ch, CURLOPT_HTTPGET, 1);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $header_out);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

		$cookieBuffer = array();
		foreach(  $this->cookies as $k=>$c ) $cookieBuffer[] = "$k=$c";
		curl_setopt($ch, CURLOPT_COOKIE, implode("; ",$cookieBuffer) );
		
		$data        = curl_exec($ch);
		$header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
		$header="";
		$header      = substr($data, 0, $header_size);

		$information = curl_getinfo($ch);
		curl_close($ch);

		preg_match("/Location: (.*)\r/i", $header, $ww);
		$where=$ww[1];

		//
		// Final step in oauth2

		$header_out = array(
			'Accept-Encoding: gzip, deflate, sdch',
			'Accept-Language: en-US,en;q=0.8',
			'User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_9_5) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/45.0.2454.85 Safari/537.36',
			'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
			'Cache-Control: max-age=0',
			'Connection: keep-alive',
		);

		$ch= curl_init();
		curl_setopt($ch, CURLOPT_URL, $where);
		curl_setopt($ch, CURLOPT_HEADER, 1);
		curl_setopt($ch, CURLINFO_HEADER_OUT, 1);
		curl_setopt($ch, CURLOPT_HTTPGET, 1);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $header_out);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

		$cookieBuffer = array();
		foreach(  $this->cookies as $k=>$c ) $cookieBuffer[] = "$k=$c";
		curl_setopt($ch, CURLOPT_COOKIE, implode("; ",$cookieBuffer) );
		
		$data        = curl_exec($ch);
		$header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
		$header="";
		$header      = substr($data, 0, $header_size);

		preg_match_all("/^Set-Cookie: (.*?);/ism", $header, $cookies);
		foreach( $cookies[1] as $cookie ){
			$buffer_explode = strpos($cookie, "=");
			$this->cookies[ substr($cookie,0,$buffer_explode) ] = substr($cookie,$buffer_explode+1);
		}

		$information = curl_getinfo($ch);

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
	
		$header = array(
			'Origin: https://www.pinterest.com',
			'Accept-Encoding: gzip, deflate',
			'Accept-Language: en-US,en;q=0.8',
			'X-Requested-With: XMLHttpRequest',
			'X-NEW-APP: 1',
			'X-APP-VERSION: cc98082',
			'X-CSRFToken: 6m5X9Bq08FJPzuDsQVHEYZ535jrobzjP',
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
		
		//print json_encode($this->cookies);
	}
	
	public function GetReport($domain, $startdate, $enddate) {	
	
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
			curl_setopt($ch, CURLOPT_URL, 'https://analytics.pinterest.com/analytics/domain/carnegiemnh.org/export/?application=all&tab=repins&end_date='.$enddate.'&start_date=2012-11-01');
			$content = curl_exec($ch);
			curl_close($ch);
		
			return parse_csv( $content );
			
		}
			
	}

}

Class Tumblr {

	private $blog;
	private $ckey;
	
	public function Tumblr($blog_name, $consumer_key) {

		$this->blog=$blog_name;
		$this->ckey=$consumer_key;

	}
	
	public function GetReport($startdate, $enddate) {
		
		$startdate_epoch=DateTime::createFromFormat('Y-m-d H:i:s', $startdate." 00:00:00")->getTimeStamp();
		$enddate_epoch=DateTime::createFromFormat('Y-m-d H:i:s', $enddate." 23:59:59")->getTimeStamp();

		$offset=0;
		$limit=20;
		$overflow=1000;
		$last_timestamp=0;
		$total_posts=0;
		$post_count=0;
		$notecount=0;
		$notecount_m=0;
		
		while ($overflow>0) {
			
			$overflow-=1;
			
			$ch = curl_init();

			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_HEADER, 0);
			curl_setopt($ch, CURLINFO_HEADER_OUT, 0);
			curl_setopt($ch, CURLOPT_POST, 0);
			curl_setopt($ch, CURLOPT_URL, 'http://api.tumblr.com/v2/blog/'.$this->blog.'/posts?api_key='.$this->ckey.'&notes_info=true&offset='.$offset.'&limit='.$limit);
			
			$content = curl_exec($ch);

			curl_close($ch);

			$j=json_decode($content);
			$total_posts=$j->{'response'}->{'total_posts'};
			
			foreach ($j->{'response'}->{'posts'} as $p=>$v) {
				
				$post_count++;
			
				$ts=$j->{'response'}->{'posts'}[$p]->{'timestamp'};	

				$last_timestamp=$ts;
				
				if ($ts<=$enddate_epoch && $ts>=$startdate_epoch) {
										
					foreach ($ts=$j->{'response'}->{'posts'}[$p]->{'notes'} as $q=>$r) {
					
						if ($j->{'response'}->{'posts'}[$p]->{'notes'}[$q]->{'type'} != 'posted') {
							
							$notecount_m++;
							
						}
						
					}
					
				} else if ($ts<$startdate_epoch) {
					
					foreach ($ts=$j->{'response'}->{'posts'}[$p]->{'notes'} as $q=>$r) {
					
						if ($j->{'response'}->{'posts'}[$p]->{'notes'}[$q]->{'type'} != 'posted') {
							
							$notecount++;
							
						}
				
					}
					
				}
				
			}
			
			if ($post_count>=$total_posts) {
			
				break;
				
			}
		
			$offset+=$limit;
		
		}
		
		$res=array();
		
		$res['this_month']=$notecount_m;
		$res['last_month']=$notecount;

		return $res;
	
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
