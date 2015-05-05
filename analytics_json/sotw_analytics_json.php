<?php
  
	error_reporting(E_ALL & ~E_NOTICE);

	//
	// Make sure tis points to the correct files

	require_once 'analytics.php';

	//
	// This file needs to be modified to contain the primary and secondary property GA view IDs from Google Analytics

	require_once 'ga_accounts.php';

	// Running the server on a different domain or from your local machine?
	// You will need to uncomment or adjust this 
	//
	//header('Access-Control-Allow-Origin: *'); 

	/* Get analytics object */
	$client = getClient();
	$token = Authenticate($client);
	$analytics = new Google_Service_Analytics($client);

	$account = $ga_accounts['primary'];
	if ($_GET['property']!="") {
	$account = $ga_accounts[$_GET['property']];
	}
	$av2="";

	//
	// dates can be set in query string, eg: month=xx&year=xxxx
	// If no month/year specified, use last month of current date as basis

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

	$startdate=date("Y-m-d", strtotime($year."-".$month."-01"));
	$enddate=date("Y-m-t", strtotime($startdate));
	$p_startdate=date("Y-m-d", strtotime($p_year."-".$p_month."-01"));
	$p_enddate=date("Y-m-t", strtotime($p_startdate));

	$av2['results']['start_date']=$startdate;
	$av2['results']['end_date']=$enddate;

	$result=get_result($analytics, $account, $startdate, $enddate, "ga:pageviews,ga:avgSessionDuration,ga:visits","ga:deviceCategory,ga:date","","1000","");
	$result_p=get_result($analytics, $account, $p_startdate, $p_enddate, "ga:pageviews,ga:avgSessionDuration,ga:visits","ga:deviceCategory,ga:date","","1000","");

	foreach($result['rows'] as $adata) {
  
	  $page_views[ $adata[0] ]+=$adata[2];
	  $session_duration[ $adata[0] ]+=$adata[3];
	  $visits[ $adata[0] ]+=$adata[4];
	  $count[ $adata[0] ]++;

	  if ($adata[4]>$topday_visits[$adata[0]]) { $topday_visits[$adata[0]]=$adata[4];$topday[$adata[0]]=$adata[1];}
  
	  $visits_date[ $adata[1] ]+=$adata[4];
  
	}

	foreach($result_p['rows'] as $adata) {
  
	  $p_page_views[ $adata[0] ]+=$adata[2];
	  $p_session_duration[ $adata[0] ]+=$adata[3];
	  $p_visits[ $adata[0] ]+=$adata[4];
	  $p_count[ $adata[0] ]++;
  
	}

	$pastmonth_visits=0;
	$pastmonth_pv=0;

	foreach($page_views as $device=>$value) {
  
	  $av2['overview'][$device]['visits']['current']=$visits[$device];
	  $av2['overview'][$device]['visits']['change']=($visits[$device]-$p_visits[$device])/$p_visits[$device];

	  $av2['overview'][$device]['page_views']['current']=$page_views[$device];
	  $av2['overview'][$device]['page_views']['change']=($page_views[$device]-$p_page_views[$device])/$p_page_views[$device];

	  $av2['overview'][$device]['pages_per_visit']['current']=$page_views[$device]/$visits[$device];
	  $av2['overview'][$device]['pages_per_visit']['change']=(($page_views[$device]/$visits[$device])-($p_page_views[$device]/$p_visits[$device]))/($p_page_views[$device]/$p_visits[$device]);

	  $av2['overview'][$device]['avg_time_on_site']['current']=$session_duration[$device]/$count[$device];
	  $av2['overview'][$device]['avg_time_on_site']['change']=(($session_duration[$device]/$count[$device])-($p_session_duration[$device]/$p_count[$device]))/($p_session_duration[$device]/$p_count[$device]);

	  $av2['overview'][$device]['top_day']=$topday[$device]; 

	  $av2['overview']['totals']['visits']['current']+=$visits[$device];
	  $av2['overview']['totals']['page_views']['current']+=$page_views[$device];

	  $pastmonth_visits+=$p_visits[$device];
	  $pastmonth_pv+=$p_page_views[$device];

	}

	$av2['overview']['totals']['visits']['change']=($av2['overview']['totals']['visits']['current']-$pastmonth_visits)/$pastmonth_visits;
	$av2['overview']['totals']['page_views']['change']=($av2['overview']['totals']['page_views']['current']-$pastmonth_pv)/$pastmonth_pv;

	$av2['overview']['totals']['pages_per_visit']['current']=$av2['overview']['totals']['page_views']['current']/$av2['overview']['totals']['visits']['current'];
	$av2['overview']['totals']['pages_per_visit']['change']=(($av2['overview']['totals']['page_views']['current']/$av2['overview']['totals']['visits']['current'])-($pastmonth_pv/$pastmonth_visits))/($pastmonth_pv/$pastmonth_visits);


	$av2['overview']['visits_per_date']=$visits_date;

	$history_enddate=$p_enddate;

	$history_start_month=$p_month-10;
	$history_start_year=$p_year;
	if ($history_start_month<0) {$history_start_month+=12;$history_start_year--;}
	$history_startdate=date("Y-m-d", strtotime($history_start_year."-".$history_start_month."-01"));


	$result=get_result($analytics, $account, $history_startdate, $history_enddate, "ga:pageviews","ga:date","","1000","");

	$av2['historic']=$result['rows'];  


	$result=get_result($analytics, $account, $startdate, $enddate, "ga:pageviews","ga:pagePath,ga:pageTitle","-ga:pageviews","1000","");
	$av2['top_pages']=$result['rows'];

	$result=get_result($analytics, $account, $startdate, $enddate, "ga:pageviews","ga:source,ga:keyword","-ga:pageviews","1000","ga:keyword!=(not set);ga:keyword!=(not provided)");

	foreach($result['rows'] as $sdata) {
  
	  $top_terms[ $sdata[1] ]+=$sdata[2];
	}

	$ii=0;
	foreach($top_terms as $term=>$hits) {

	  $xx["term"]=$term;$xx["hits"]=$hits;
	  $av2['top_search_terms'][$ii]["term"]=$term;
	  $av2['top_search_terms'][$ii]["hits"]=$hits;
	  $ii++;
	  
	}

	//
	// Incoming traffic profile (channel grouping)


	$result=get_result($analytics, $account, $startdate, $enddate, "ga:visits","ga:channelGrouping","","1000");
	$av2['incoming_traffic_sources']['channel_grouping']=$result['rows'];

	// 
	// Top referrers

	$result=get_result($analytics, $account, $startdate, $enddate, "ga:visits","ga:source","-ga:visits","1000","ga:source!@(direct);ga:source!@bing;ga:source!@yahoo;ga:source!@google;");
	foreach($result['rows'] as $rdata) {
  
	  $top_referrers[ $rdata[0] ]+=$rdata[1];
	}

	$ii=0;
	foreach($top_referrers as $term=>$hits) {

	  $av2['incoming_traffic_sources']['top_referrers'][$ii]["referrer"]=$term;
	  $av2['incoming_traffic_sources']['top_referrers'][$ii]["hits"]=$hits;
	  $ii++;
	  
	}

	//
	// Top campaigns

	$result=get_result($analytics, $account, $startdate, $enddate, "ga:visits","ga:campaign,ga:medium","-ga:visits","1000","ga:campaign!@not set");

	$av2["campaigns"]=$result['rows'];
	print json_encode($av2);

	function get_result($analytics, $account, $startdate, $enddate, $metrics, $dimmensions, $sort, $maxresults, $filters, $segment) {
  
	  $success=0;
	  while ($success==0) {
	  
		  $success=1;
		  try {
				$result=runQuery($analytics, $account, $startdate, $enddate, $metrics, $dimmensions, $sort, $maxresults, $filters, $segment);
		  } catch (Exception $e) {
			   $success=0;
			   sleep(1);
		  }
	
	  }
	  return $result;
  
	}
  
 ?>