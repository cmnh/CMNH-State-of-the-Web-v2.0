var up_arrow="<i style=\"color:blue; vertical-align: top; padding-top: 0px;\" class=\"fa fa-arrow-up\"></i>";
var down_arrow="<i style=\"color:red; vertical-align: top; padding-top: 2px;\" class=\"fa fa-arrow-down\"></i>";
var Months=['January','February','March','April','May','June','July','August','September','October','November','December'];

//
// Secondary web properties object array
// The index name must match the index name in the analytics_json/ga_accounts.php file
//
// ex:	
//   var secondary_properties=[{title: 'After Dark Microsite',url:'http://afterdark.carnegiemnh.org', index: 'afterdark'},
//			  {title: 'Bird Safe Pittsburgh',url: 'http://www.birdsafepgh.org', index: 'birdsafepgh'},
//			  {title: '2015 Summer Camps',url: 'http://www.artandnaturalhistory.org/camps', index: 'summercamps'} ];

var secondary_properties=[ {title: '',url: '', index: ''} ];

//
// HTML for secondary web properties
var swp_html="<div style=\"clear: both; width: 100%; padding-left: 1em; text-align: left; position: relative; page-break-inside: avoid;\"><span id=\"##INDEX##_swp_title\" style=\"font-weight: bold; text-align: left;\"></span><br/><div style=\"clear: both; float: left; width: 35%;\"><table style=\"width: 100%;\"><tr><td style=\"width: 50%;\">Visits</td><td id=\"##INDEX##_swp_visits\" style=\"width: 50%; text-align: right; font-weight: bold;\"></td></tr><tr><td style=\"width: 50%;\">Page Views</td><td id=\"##INDEX##_swp_pv\" style=\"width: 50%; text-align: right; font-weight: bold;\"></td></tr><tr><td style=\"width: 50%;\">Avg. Time</td><td id=\"##INDEX##_swp_avgtime\" style=\"width: 50%; text-align: right; font-weight: bold;\"></td></tr></table><div id=\"##INDEX##_swp_hc-platform_metrics\" style=\"float: left; width: 300px; height: 250px; margin: 0 auto\"></div></div><div style=\"float: left; width: 65%;\"><table id=\"##INDEX##_swp_referrers\" style=\"margin-left: 1em; width: 100%; overflow: hidden;\"><tr style=\"font-size: 50%; text-decoration: underline; \"><td colspan=\"2\" style=\"width: 70%;\">Top Referrers</td></tr></table><div id=\"##INDEX##_swp_hc-current_activity\" style=\"float: left; width: 100%; height: 175px; margin: 0 auto\"></div></div><div>";

$(document).ready(function() {

	$.getJSON( "analytics_json/sotw_analytics_json.php", function( data ) {
	
		//
		// Set overview

		var rpt_start_date=new Date(dodate(data.results.start_date));
		var rpt_end_date=new Date(dodate(data.results.end_date));

		if (rpt_start_date.getMonth()==rpt_end_date.getMonth()) {
		
			$(".report_date").html(Months[rpt_start_date.getMonth()]+"&nbsp;"+rpt_start_date.getFullYear());
			
		}

		$("#overview_visits").text( parseInt (data.overview.totals.visits.current).toLocaleString() );
		pct_change(data.overview.totals.visits.change, $("#overview_visits_change"));

		$("#overview_pageviews").text( parseInt (data.overview.totals.page_views.current).toLocaleString() );
		pct_change(data.overview.totals.page_views.change, $("#overview_pageviews_change"));

		$("#overview_ppv").text( data.overview.totals.pages_per_visit.current.toLocaleString('en-US', {maximumFractionDigits: '2'}) );
		pct_change(data.overview.totals.pages_per_visit.change, $("#overview_ppv_change"));
		
		var historic_csv="Count,Page Views\n";
		for (var x in data.historic) {
			historic_csv+=data.historic[x][0].substring(0,4)+"-"+data.historic[x][0].substring(4,6)+"-"+data.historic[x][0].substring(6,8)+","+data.historic[x][1]+"\n";
		}

		var current_csv="Count,Page Views\n";
		for (var x in data.overview.visits_per_date) {
			current_csv+=x.substring(0,4)+"-"+x.substring(4,6)+"-"+x.substring(6,8)+","+data.overview.visits_per_date[x]+"\n";
		}
		
		//
		// Overview month, historic charts
		
		$("#hc-11mo_activity").highcharts({

			credits: {
    		  enabled: false
  			},

            data: {
                csv: historic_csv
            },

            title: {
                text: ''
            },
            
            yAxis: {
            	min: 0,
            	title: {
            		enabled: false
            	}
            },
            xAxis: {
            	showLastLabel: true
            },
            
            plotOptions: {
            	line: {
            		marker: {
            			enabled: false
            		},
            		showInLegend: false
            	}
            }

        });		

		$("#hc-current_activity").highcharts({

			credits: {
				enabled: false
			},

            data: {
                csv: current_csv
            },

            title: {
                text: ''
            },
            
            yAxis: {
            	min: 0,
            	title: {
            		enabled: false
            	}
            },
            
            plotOptions: {
            	line: {
            		marker: {
            			enabled: false
            		},
            		showInLegend: false
            	}
            }

        });
        
        //
        // Per-platform metrics
        
        $("#ppm_visits_desktop_current").text( parseInt (data.overview.desktop.visits.current).toLocaleString() );
		pct_change(data.overview.desktop.visits.change, $("#ppm_visits_desktop_change"));

        $("#ppm_visits_mobile_current").text( parseInt (data.overview.mobile.visits.current).toLocaleString() );
		pct_change(data.overview.mobile.visits.change, $("#ppm_visits_mobile_change"));

        $("#ppm_visits_tablet_current").text( parseInt (data.overview.tablet.visits.current).toLocaleString() );
		pct_change(data.overview.tablet.visits.change, $("#ppm_visits_tablet_change"));


        $("#ppm_page_views_desktop_current").text( parseInt (data.overview.desktop.page_views.current).toLocaleString() );
		pct_change(data.overview.desktop.page_views.change, $("#ppm_page_views_desktop_change"));

        $("#ppm_page_views_mobile_current").text( parseInt (data.overview.mobile.page_views.current).toLocaleString() );
		pct_change(data.overview.mobile.page_views.change, $("#ppm_page_views_mobile_change"));

        $("#ppm_page_views_tablet_current").text( parseInt (data.overview.tablet.page_views.current).toLocaleString() );
		pct_change(data.overview.tablet.page_views.change, $("#ppm_page_views_tablet_change"));


        $("#ppm_ppv_desktop_current").text(  (data.overview.desktop.pages_per_visit.current).toLocaleString('en-US', {maximumFractionDigits: '2'}) );
		pct_change(data.overview.desktop.pages_per_visit.change, $("#ppm_ppv_desktop_change"));

        $("#ppm_ppv_mobile_current").text(  (data.overview.mobile.pages_per_visit.current).toLocaleString('en-US', {maximumFractionDigits: '2'}) );
		pct_change(data.overview.mobile.pages_per_visit.change, $("#ppm_ppv_mobile_change"));

        $("#ppm_ppv_tablet_current").text(  (data.overview.tablet.pages_per_visit.current).toLocaleString('en-US', {maximumFractionDigits: '2'}) );
		pct_change(data.overview.tablet.pages_per_visit.change, $("#ppm_ppv_tablet_change"));


        $("#ppm_avgtime_desktop_current").text( friendly_time(data.overview.desktop.avg_time_on_site.current) );
		pct_change(data.overview.desktop.avg_time_on_site.change, $("#ppm_avgtime_desktop_change"));

        $("#ppm_avgtime_mobile_current").text( friendly_time(data.overview.mobile.avg_time_on_site.current) );
		pct_change(data.overview.mobile.avg_time_on_site.change, $("#ppm_avgtime_mobile_change"));

        $("#ppm_avgtime_tablet_current").text( friendly_time(data.overview.tablet.avg_time_on_site.current) );
		pct_change(data.overview.tablet.avg_time_on_site.change, $("#ppm_avgtime_tablet_change"));


        $("#ppm_top_day_desktop_current").text( dodate(data.overview.desktop.top_day).slice(0,-4) );
        $("#ppm_top_day_mobile_current").text( dodate(data.overview.mobile.top_day).slice(0,-4) );
        $("#ppm_top_day_tablet_current").text( dodate(data.overview.tablet.top_day).slice(0,-4) );
		
		var ppm_chart_data=[
			{name: 'Desktop', y: data.overview.desktop.visits.current, color: '#6faddb' },
            {name: 'Mobile',  y: data.overview.mobile.visits.current,  color: '#f09048' },
            {name: 'Tablet',  y: data.overview.tablet.visits.current,  color: '#679149' } 
        ];

		$("#hc-platform_metrics").highcharts({

			credits: {
    		  enabled: false
  			},
			title: {
                text: ''
            },
			series: [{
	            type: 'pie',
				borderWidth: 4,
 	            dataLabels: {
 	            	style: {
                        textShadow: ''
                    },
 	            	connectorWidth: 0,
 	            	distance: -50,
 	            	formatter: function() {
                        //return "<span style=\"color: "+this.point.color+";\">"+this.key+"</span><br/><span style=\"color: "+this.point.color+";\">"+(this.percentage/100).toLocaleString('en-US', {style: 'percent', maximumFractionDigits: '0'})+"</span>" ;
                        return "<span style=\"color: #000\">"+this.key+"</span><br/><span style=\"color: #000\">"+(this.percentage/100).toLocaleString('en-US', {style: 'percent', maximumFractionDigits: '0'})+"</span>" ;
                    }
 	            },
 	            name: '',
	            data: ppm_chart_data
            }]			

		});

		//
		// Top pages
	
		var top_pages_bg_colors=['#EEE','#FFF'];
		var top_pages_color_alternate=0;
		var top_pages_count=0;
		for (var i in data.top_pages) {	
			
			if (data.top_pages[i][0]!="/") {
			
				var title=data.top_pages[i][1];
				title=title.replace(" : ","");
				title=title.replace("Carnegie Museum of Natural History","");
			
				var html="<tr style=\"background-color: "+top_pages_bg_colors[top_pages_color_alternate]+"\"><td style=\"text-align: right;\">"+data.top_pages[i][2]+"</td><td style=\"text-align: right;\">"+(data.top_pages[i][2]/data.overview.totals.visits.current).toLocaleString('en-US',{style: 'percent', maximumFractionDigits: '1'})+"</td><td>"+data.top_pages[i][0]+"</td><td><div style=\"display: block; max-height: 1.35em; overflow: hidden;\">"+title+"</div></td></tr>";

				$("#top_pages").append(html);

				top_pages_color_alternate=Math.abs(top_pages_color_alternate-1);

				top_pages_count++;
				if (top_pages_count==10) {
					break;
				}
			}		
		}

		//
		// Top search terms
		
		var top_terms=data.top_search_terms;
		top_terms.sort(st_compare);
		
		var top_st_bg_colors=['#EEE','#FFF'];
		var top_st_color_alternate=0;
		for (var i=0;i<12; i+=2) {	
						
			var html="<tr style=\"background-color: "+top_pages_bg_colors[top_pages_color_alternate]+"\"><td style=\"overflow: hidden; width: 50%; max-width: 390px;\"><div style=\"display: block; max-height: 2.5em; overflow: hidden;\">"+top_terms[i].term+"</div></td><td style=\"overflow: hidden; width: 50%; max-width: 390px;\"><div style=\"display: block; max-height: 2.5em; overflow: hidden;\">"+top_terms[i+1].term+"</div></td></tr>";
			$("#top_search_terms").append(html);
			top_pages_color_alternate=Math.abs(top_pages_color_alternate-1);
					
		}

		//
		// Incoming traffic profile
		
		var itp_colors=['#6faddb','#f09048','#679149','#CFCF00','#FF66CC','#FF9980'];
		
		var itp_chart_data=new Array();
		for (var i in data.incoming_traffic_sources.channel_grouping) {
		
			itp_chart_data.push({name: data.incoming_traffic_sources.channel_grouping[i][0], y:parseInt(data.incoming_traffic_sources.channel_grouping[i][1]), color: itp_colors[i]});
 		
		}

		$("#hc-incoming_traffic_profile").highcharts({

			credits: {
    		  enabled: false
  			},
			title: {
                text: ''
            },
			series: [{
	            type: 'pie',
				borderWidth: 4,
 	            dataLabels: {
 	            	style: {
                        
                    },
 	            	connectorWidth: 1,
 	            	distance: 30,
 	            	formatter: function() {
                        return "<span style=\"color: "+this.point.color+";\">"+this.key+"</span><br/><span style=\"color: "+this.point.color+";\">"+(this.percentage/100).toLocaleString('en-US', {style: 'percent', maximumFractionDigits: '1'})+"</span>" ;
                    }
 	            },
 	            name: '',
	            data: itp_chart_data
            }]			

		});	
		
		//
		// Top referrers

		var top_referrers=data.incoming_traffic_sources.top_referrers;
		top_referrers.sort(st_compare);
		
		var top_r_bg_colors=['#EEE','#FFF'];
		var top_r_color_alternate=0;
		for (var i=0;i<10; i++) {	
						
			var html="<tr style=\"background-color: "+top_r_bg_colors[top_r_color_alternate]+"\"><td style=\"overflow: hidden; width: 50%; max-width: 390px;\">"+top_referrers[i].referrer+"</td></tr>";
			$("#top_referrers").append(html);
			top_r_color_alternate=Math.abs(top_r_color_alternate-1);
					
		}
		
		//
		// Top campaigns
		
		var top_campaigns_bg_colors=['#EEE','#FFF'];
		var top_campaigns_color_alternate=0;
		var top_campaigns_count=0;
		for (var i in data.campaigns) {	
		
			var html="<tr style=\"background-color: "+top_campaigns_bg_colors[top_campaigns_color_alternate]+"\"><td style=\"text-align: left;\">"+data.campaigns[i][0]+"</td><td style=\"text-align: left;\">"+data.campaigns[i][1]+"</td><td>"+data.campaigns[i][2]+"</td></tr>";
			$("#top_campaigns").append(html);
			top_campaigns_color_alternate=Math.abs(top_campaigns_color_alternate-1);

		}

	});

	//
	// Process social media
	
	$.getJSON( "analytics_json/socialmedia.php", function( data ) {
	
		console.log(data);

		//
		// Build list of platforms/metrics

		var platforms=new Array();
		
		var platform_count=0;
		
		for (var a in data.accounts) {
		
			for (var p in data.accounts[a].platforms) {

				if (!platforms[ data.accounts[a].platforms[p].platform ]) {
					platforms[ data.accounts[a].platforms[p].platform ]=new Object();
					platforms[ data.accounts[a].platforms[p].platform ].platform=data.accounts[a].platforms[p].platform;
					
					platform_count++;
				}
				platforms[ data.accounts[a].platforms[p].platform ].metrics=new Array();
				
				for (var m in data.accounts[a].platforms[p].metrics) {
				
					if (!platforms[ data.accounts[a].platforms[p].platform ].metrics[ data.accounts[a].platforms[p].metrics[m].metric ]) {
						
						platforms[ data.accounts[a].platforms[p].platform ].metrics.push(data.accounts[a].platforms[p].metrics[m].metric);
						
					}
					
				}
				
			}
			
		}
		
		//
		// Build table
		
		//
		// Headers
		
		var sm_table=$("<table id=\"sm_table\"><tr id=\"sm_table_header\"><td class=\"sm_accounts\"></td></tr><tr id=\"sm_table_metrics\"><td class=\"sm_accounts\"></td></tr><tr id=\"sm_table_accounts\"></tr></table>");
		sm_columns_ref=new Array();
		var sm_column_count=0;

		for (var p in platforms) {
		
			var colspan="colspan=\""+(platforms[p].metrics.length*2)+"\"";
			$(sm_table).find("#sm_table_header").append("<td "+colspan+" class	=\"sm_header\"><img src=\"images/ns_"+p+".png\">"+p+"</td>");

			for (var m in platforms[p].metrics) {
				$(sm_table).find("#sm_table_metrics").append("<td colspan=\"2\" class=\"sm_metrics\">"+platforms[p].metrics[m]+"</td>");
				sm_columns_ref[sm_column_count]={platform: p, metric: platforms[p].metrics[m], metric_total: 0, metric_change_total:0 };
				sm_column_count++;
			}
			
		}
		
		//
		// Per platform, per metric data
		
		var sm_bg_colors=['#EEE','#FFF'];
		var sm_color_alternate=0;

		for (var a in data.accounts) {

			sm_table.append("<tr id=\"sm_table_metrics_row_"+a+"\" style=\"background-color: "+sm_bg_colors[sm_color_alternate]+";\"></tr>");
			sm_color_alternate=Math.abs(sm_color_alternate-1);

			$(sm_table).find("#sm_table_metrics_row_"+a).append("<td class=\"sm_accounts\">"+data.accounts[a].title+"</td>");

			for (var c=0; c<sm_column_count; c++) {

				var found_pm_match=0;
			
				for (var p in data.accounts[a].platforms) {

					for (var m in data.accounts[a].platforms[p].metrics) {
					
						if (data.accounts[a].platforms[p].platform==sm_columns_ref[c].platform && data.accounts[a].platforms[p].metrics[m].metric==sm_columns_ref[c].metric) {
						
							$(sm_table).find("#sm_table_metrics_row_"+a).append("<td class=\"sm_metrics_data\">"+parseInt(data.accounts[a].platforms[p].metrics[m].value).toLocaleString()+"</td>");
							
							var metric_change="";
							
							if (data.accounts[a].platforms[p].metrics[m].change>0) {
								metric_change="<span style=\"color:green;\">(+"+parseInt(data.accounts[a].platforms[p].metrics[m].change).toLocaleString()+")</span>";
							} else if (data.accounts[a].platforms[p].metrics[m].change<0) {
								 metric_change="<span style=\"color:red;\"(-"+parseInt(data.accounts[a].platforms[p].metrics[m].change).toLocaleString()+")</span>";
							}
							
							$(sm_table).find("#sm_table_metrics_row_"+a).append("<td class=\"sm_metrics_data_change\">"+metric_change+"</td>");
							
							sm_columns_ref[c].metric_total+=parseInt(data.accounts[a].platforms[p].metrics[m].value);
							sm_columns_ref[c].metric_change_total+=parseInt(data.accounts[a].platforms[p].metrics[m].change);
							found_pm_match=1;
							
						}
						
					}
					
				}
				
				if (found_pm_match==0) {
				
					$(sm_table).find("#sm_table_metrics_row_"+a).append("<td></td><td></td>");
					
				}
				
			}

		}
		
		//
		// Per metric Totals
		
		sm_table.append("<tr id=\"sm_table_metrics_row_total\" class=\"sm_metrics_totals\"><td class=\"sm_accounts\">Total</td></tr>");
		
		for (var c=0; c<sm_column_count; c++) {

			$(sm_table).find("#sm_table_metrics_row_total").append("<td class=\"sm_metrics_metric_total\">"+(sm_columns_ref[c].metric_total).toLocaleString()+"</td>");

			var metric_total_change="";
			
			if (sm_columns_ref[c].metric_change_total>0) {
				metric_total_change="<span style=\"color:green;\">(+"+(sm_columns_ref[c].metric_change_total).toLocaleString()+")</span>";
			} else if (sm_columns_ref[c].metric_change_total<0) {
				 metric_total_change="<span style=\"color:red;\"(-"+(sm_columns_ref[c].metric_change_total).toLocaleString()+")</span>";
			}

			$(sm_table).find("#sm_table_metrics_row_total").append("<td class=\"sm_metrics_metric_change_total\">"+metric_total_change+"</td>");

		}

		$("#social_media_report").append(sm_table);

	});

	//
	// Process secondary properties
	
	var secondary_page=1;
	var secondary_ppp=3; // properties per page
	var sppp_count=0;
	
	for (var sp in secondary_properties) {

		var twp=swp_html.replace(/##INDEX##/g, secondary_properties[sp].index);
		$("#secondary_webp_"+secondary_page).append(twp);

		$.getJSON( "analytics_json/sotw_analytics_json.php?property="+secondary_properties[sp].index, (function( index_name, title, url ) {

			return function(sp_data) {

				//
				// Secondary property
				// Title and overview

				$("#"+index_name+"_swp_title").html(title+"&nbsp;("+url+")" );
				$("#"+index_name+"_swp_visits").text(parseInt (sp_data.overview.totals.visits.current).toLocaleString() );
				$("#"+index_name+"_swp_pv").text(parseInt (sp_data.overview.totals.page_views.current).toLocaleString() );
				$("#"+index_name+"_swp_avgtime").text(friendly_time(sp_data.overview.desktop.avg_time_on_site.current) );

				//
				// Platform breakdown

				var desktop_curr_visits=0;
				var mobile_curr_visits=0;
				var tablet_curr_visits=0;

				if (sp_data.overview.hasOwnProperty('desktop')) {desktop_curr_visits=sp_data.overview.desktop.visits.current;}
				if (sp_data.overview.hasOwnProperty('mobile')) {mobile_curr_visits=sp_data.overview.mobile.visits.current;}
				if (sp_data.overview.hasOwnProperty('tablet')) {tablet_curr_visits=sp_data.overview.tablet.visits.current;}

				var ppm_chart_data=[
					{name: 'Desktop', y: desktop_curr_visits, color: '#6faddb' },
					{name: 'Mobile',  y: mobile_curr_visits,  color: '#f09048' },
					{name: 'Tablet',  y: tablet_curr_visits,  color: '#679149' } 
				];

				$("#"+index_name+"_swp_hc-platform_metrics").highcharts({

					credits: {
					  enabled: false
					},
					title: {
						text: ''
					},
					plotOptions: {
						pie: {
							allowPointSelect: true,
							cursor: 'pointer',
							dataLabels: {
								enabled: false
							},
							showInLegend: true
						}
					},
					series: [{
						type: 'pie',
						borderWidth: 2,
						dataLabels: {
							style: {
								textShadow: ''
							},
							connectorWidth: 0,
							distance: -50,
							formatter: function() {
								return "<span style=\"color: #000\">"+this.key+"</span><br/><span style=\"color: #000\">"+(this.percentage/100).toLocaleString('en-US', {style: 'percent', maximumFractionDigits: '0'})+"</span>" ;
							}
						},
						name: '',
						data: ppm_chart_data
					}]			

				});
				
				//
				// Page Vists over month
				
				var current_csv="Count,Page Views\n";
				for (var x in sp_data.overview.visits_per_date) {
					current_csv+=x.substring(0,4)+"-"+x.substring(4,6)+"-"+x.substring(6,8)+","+sp_data.overview.visits_per_date[x]+"\n";
				}
				
				$("#"+index_name+"_swp_hc-current_activity").highcharts({

					credits: {
						enabled: false
					},

					data: {
						csv: current_csv
					},

					title: {
						text: 'Page Visits'
					},
			
					yAxis: {
						min: 0,
						title: {
							enabled: false
						}
					},
			
					plotOptions: {
						line: {
							marker: {
								enabled: false
							},
							showInLegend: false
						}
					}

				});
				
				//
				// Top referrers

				var top_referrers=sp_data.incoming_traffic_sources.top_referrers;
				top_referrers.sort(st_compare);
		
				var top_r_bg_colors=['#EEE','#FFF'];
				var top_r_color_alternate=0;
				for (var i=0;i<10; i+=2) {	
						
					var html="<tr style=\"background-color: "+top_r_bg_colors[top_r_color_alternate]+"\"><td style=\"overflow: hidden; width: 50%; max-width: 50%;\">"+top_referrers[i].referrer+"</td><td style=\"overflow: hidden; width: 50%; max-width: 50%;\">"+top_referrers[i+1].referrer+"</td></tr>";
					$("#"+index_name+"_swp_referrers").append(html);
					top_r_color_alternate=Math.abs(top_r_color_alternate-1);
					
				}
		
		
			};
			
		})(secondary_properties[sp].index, secondary_properties[sp].title, secondary_properties[sp].url));
		
		sppp_count++;
		if (sppp_count==secondary_ppp) {
			sppp_count=0;
			secondary_page++;
		}
		
	}
		
	
	
});

function st_compare(a,b) {
  if (a.hits > b.hits)
     return -1;
  if (a.hits < b.hits)
    return 1;
  return 0;
}


function dodate(x,o) {

	x=x.replace(/-/g,"");
	var d=new Date(x.substring(4,6)+"/"+x.substring(6,8)+"/"+x.substring(0,4));
	var date_options = { weekday: 'short', year: '2-digit', month: 'short', day: 'numeric' };
	if (o) {date_options=o;}
	var dout=d.toLocaleString('en-US', date_options);
	return dout;

}

function friendly_time(value) {

	var m=parseInt(value/60);
	var s=value-(m*60);
	
	return m+"m"+parseInt(s)+"s";
	
}

function pct_change(value, element) {

	var contents="";
	if (value<0) {
		contents=down_arrow;
		element.css('color','red');
		value=Math.abs(value);
	} else {
		contents=up_arrow;
		element.css('color','blue');
	}
	contents+=value.toLocaleString('en-US', {style: 'percent', maximumFractionDigits: '1'});
	element.html(contents);
	
}
	