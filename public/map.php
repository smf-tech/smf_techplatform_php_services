<!DOCTYPE html >
  <head>
    <meta name="viewport" content="initial-scale=1.0, user-scalable=no" />
    <meta http-equiv="content-type" content="text/html; charset=UTF-8"/>
    <title>BJS Working Locations</title>
	  <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.4.1/css/bootstrap.min.css">
	  <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>
	  <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.16.0/umd/popper.min.js"></script>
	  <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.4.1/js/bootstrap.min.js"></script>
    <style>
     #map {
        height: 100%;
      }
      html, body {
        height: 100%;
        margin: 0;
        padding: 0;
      }
	#floating-panel {
        position: absolute;
        top: 10px;
        left: 25%;
        z-index: 5;  
        text-align: center;
        font-family: 'Roboto','sans-serif';
        line-height: 30px;
        padding-left: 10px;
		width:696px;
      }
	.custom-select{
		width:28%;
	} 
	.btn{
		vertical-align: top;
	}
	
	@media only screen and (max-width: 600px) {
   #floating-panel {
	  width:296px; 
	   top: 51px;
   }
   .custom-select {
    width: 48%;
}
}
    </style>
  </head>

<html>
  <body>
	<div class="" id="floating-panel"> 
	  <form action="" id="form" name="myForm" method="POST">
		
		<select name="state" id="state" class="custom-select mb-3" onChange="getalldistrict(this.value)" required> 
		<option value="">Select State</option>
		</select>
		<select name="district" id="district" onChange="getalltaluka(this.value)" class="custom-select mb-3" required> 
		   <option value="">Select District</option> 
		</select>
		<select name="taluka" id="taluka" class="custom-select mb-3" required> 
		 <option value="">Select Taluka</option> 
		</select>
		<button type="button" onClick="filter(this)" class="btn btn-primary" id="buttons">Submit</button>
	  </form> 
	</div>
    <div id="map"></div> 
	
	 <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>
     <script>
	 
	var MainbaseUrl = "http://13.235.105.204/";
    var allmarkers = [] ;
    var map ;
    var marker ;
	
	window.onload = function() {

		$("#buttons").hide();
			$.ajax({ 
			url: MainbaseUrl+"api/getallstate", 
			type:'GET',
			success: function(states,status){ 
			$('#taluka').empty();
			$('#district') .empty();
				for(var i=0;i<states.locations.length;i++){
					if(states.locations[i].state !=null && states.locations[i].state !=null)
					  {
						$('#state').append("<option value='"+states.locations[i].state._id+"'>"+states.locations[i].state.name+"</option>");
					  }  
					}
			}
		});
	};

	function getalldistrict(stateid){
		$("#buttons").hide();
		$('#taluka').empty();
		 $.ajax({ 
			url: MainbaseUrl+"api/getalldistrict/"+stateid, 
			type:'GET',
			success: function(district,status){
				console.log(district);
				$('#district') .empty();
				$('#district').append("<option value=''>Select District</option>");
				for(var i=0;i<district.locations.length;i++){
					if(district.locations[i].district !=null && district.locations[i].district !=null)
					{
						$('#district').append("<option value='"+district.locations[i].district._id+"'>"+district.locations[i].district.name+"</option>");
					}
				}  
			}
		}); 
	}

	function getalltaluka(districtid){
		 $.ajax({ 
			url: MainbaseUrl+"api/getalltaluka/"+districtid, 
			type:'GET',
			success: function(taluka,status){
				$('#taluka').empty();
				$("#buttons").show();
				for(var i=0;i<taluka.locations.length;i++){
					if(taluka.locations[i].taluka !=null && taluka.locations[i].taluka !=null)
					{
						$('#taluka').append("<option value='"+taluka.locations[i].taluka._id+"'>"+taluka.locations[i].taluka.name+"</option>");
					}
				}  
			}
		}); 
	}
  
	function filter()
	{   
		var e = document.getElementById('taluka');
		var result = e.options[e.selectedIndex].value;
		 
		$.ajax({ 
		url: MainbaseUrl+"api/getStructures/"+result, 
		type:'GET',
		success: function(features,status){
					   
					map.setCenter(new google.maps.LatLng(20.5937, 78.9629 )); 
					 
					map.setMapTypeId(google.maps.MapTypeId.ROADMAP); 
					toggleMarkers();
					var latlngbounds = new google.maps.LatLngBounds();
					 
					var baseUrl = MainbaseUrl+'images/';
					var icons = {
					  structure: {
						icon: baseUrl +'forestmap.png'
					  },
					  machine: {
						icon: baseUrl +'machine.gif'
					  } 
					};
					
 					// Create markers.
					for (var i = 0; i < features.length; i++) {
						 
					    var myLatlng =  new google.maps.LatLng(features[i].lat, features[i].long);
					   
					    var marker = new google.maps.Marker({ 
					 	position: myLatlng,
						icon: icons[features[i].type].icon ,
						datas : features[i].id,
						lat : features[i].lat,
						long : features[i].long,
						label:''+features[i].MachineCount +'',
						map: map,
						animation: google.maps.Animation.DROP
					  });
					 
					    allmarkers.push(marker);
						var content = '<div id="content">'+
								  '<div id="siteNotice">'+
								  '</div>'+
								  '<h1 id="firstHeading" class="firstHeading">'+features[i].code+'</h1>'+
								  '<div id="bodyContent">'+
								  '<p><b>Location : '+features[i].address+'</b></p>'+
								  '<p><b>Title : '+features[i].title+'</b></p>'+
								  '<p><b>Number of Machines : '+features[i].MachineCount+'</b></p>'+
								  '</div>';
									var infowindow = new google.maps.InfoWindow(); 
						google.maps.event.addListener(marker,'rightclick', (function(marker,content,infowindow){ 
										return function() {
										   infowindow.setContent(content);
										   infowindow.open(map,marker);
										};
										map.addListener('rightclick', function() {  
										 api(latitude,longitude); 
										});
									})(marker,content,infowindow)); 
					    google.maps.event.addListener(marker, 'click', function() {
						
					
						
						map.panTo(this.getPosition());
						map.setZoom(4);
						map.setMapTypeId(google.maps.MapTypeId.ROADMAP); 
						 
						
						var lat = this.lat;
						var long = this.long;
						 
						//ajax call for machines
						$.ajax({ 
						url: MainbaseUrl+"api/getmachines/" +this.datas, 
						type:'GET',
						success: function(result,status){
							 //
							map.setCenter(new google.maps.LatLng(lat, long)); 
							map.setZoom(9); 
							toggleMarkers();
							  	
							//info windows end
							
							var latlngbounds = new google.maps.LatLngBounds();
							// Create markers.
							for (var i = 0; i < result.length; i++) { 
							    var myLatlng =  new google.maps.LatLng(result[i].lat, result[i].long); 
							    var marker = new google.maps.Marker({
								position: myLatlng,
								icon: icons[result[i].type].icon ,
								message: result[i].machinecode,
								map: map ,
								animation: google.maps.Animation.DROP
							  }); 
							    allmarkers.push(marker);
							  //info windows start
									var content = '<div id="content">'+
								  '<div id="siteNotice">'+
								  '</div>'+
								  '<h1 id="firstHeading" class="firstHeading">'+result[i].machinecode+'</h1>'+
								  '<div id="bodyContent">'+
								  '<p><b>Location : '+result[i].address+'</b></p>'+
								  '<p><b>Status : '+result[i].status+'</b></p>'+
								  '</div>';
									var infowindow = new google.maps.InfoWindow(); 
								   
									google.maps.event.addListener(marker,'rightclick', (function(marker,content,infowindow){ 
										return function() {
										   infowindow.setContent(content);
										   infowindow.open(map,marker);
										};
										map.addListener('rightclick', function() {  
										 api(latitude,longitude); 
										});
									})(marker,content,infowindow)); 
									
							   latlngbounds.extend(marker.position); 
							  //Get the boundaries of the Map.
								var bounds = new google.maps.LatLngBounds();
						 
								//Center map and adjust Zoom based on the position of all markers.
								map.setCenter(latlngbounds.getCenter());
								map.fitBounds(latlngbounds); 
							}
  
						}
						});
						//ajax ends for machine 
						
					  }); 
						latlngbounds.extend(marker.position); 
					}
					
					for (var ii = 0; ii < features.length; ii++) {
						if(features[ii].structure_boundary != null){
					var triangleCoords = [];
					for (var latln = 0; latln < features[ii].structure_boundary.length; latln++) {		
						triangleCoords.push(new google.maps.LatLng(features[ii].structure_boundary[latln].latitude,features[ii].structure_boundary[latln].longitude));
						}
						
					myPolygon = new google.maps.Polygon({
					paths: triangleCoords,
					draggable: false, // turn off if it gets annoying
					editable: false,
					strokeColor: '#FF0000',
					strokeOpacity: 0.8,
					strokeWeight: 2,
					fillColor: '#FF0000',
					fillOpacity: 0.35
				  });

				  myPolygon.setMap(map);

						
					} }		
					 
				 
				 
				 
				  
						var bounds = new google.maps.LatLngBounds();
						//Center map and adjust Zoom based on the position of all markers.
						map.setCenter(latlngbounds.getCenter());
						map.fitBounds(latlngbounds); 
		}
		});
	} 
	   
	  function getstate(){
		allmarkers.length =0; 
		var ids = 'id' ;
		$.ajax({ 
						url: MainbaseUrl+"api/getstate/state/" +ids, 
						type:'GET',
						success: function(states,status){
							 //
							map = new google.maps.Map(
							document.getElementById('map'),
							{
								center: new google.maps.LatLng(20.5937, 78.9629), zoom: 5,mapTypeId: google.maps.MapTypeId.ROADMAP});
							 
							// Create markers.
							for (var i = 0; i < states.length; i++) {
							    var myLatlng =  new google.maps.LatLng(states[i].lat, states[i].long);
							    var marker = new google.maps.Marker({
								position: myLatlng, 
								stateId : states[i].id,
								lat : states[i].lat,
								long : states[i].long,
								map: map ,
								animation: google.maps.Animation.DROP
							  }); 
							  allmarkers.push(marker);
							  google.maps.event.addListener(marker, 'click', function() {
								  
									map.panTo(this.getPosition());
									map.setZoom(7);
									var lat = this.lat;
									var long = this.long; 
									// ajax to fetch the district of selected state
									$.ajax({ 
											url: MainbaseUrl+"api/getstate/district/"+this.stateId, 
											type:'GET',
											success: function(district,status){
												 //
												toggleMarkers(); 
												map.setCenter(new google.maps.LatLng(lat, long)); 
												map.setZoom(7); 
												map.setMapTypeId(google.maps.MapTypeId.ROADMAP); 
											    
												// Create markers.
												for (var i = 0; i < district.length; i++) {
													var myLatlng =  new google.maps.LatLng(district[i].lat, district[i].long); 
													var marker = new google.maps.Marker({
													position: myLatlng, 
													districtId : district[i].id,
													lat : district[i].lat,
													long : district[i].long,
													map: map ,
													animation: google.maps.Animation.DROP
												  }); 
												  allmarkers.push(marker);
													
												  google.maps.event.addListener(marker, 'click', function() { 
														map.panTo(this.getPosition());
														map.setZoom(8);
														map.setMapTypeId(google.maps.MapTypeId.ROADMAP);  
														var lat = this.lat;
														var long = this.long; 
														// ajax to fetch the district of selected state
													$.ajax({ 
															url: MainbaseUrl+"api/getstate/taluka/"+this.districtId, 
															type:'GET',
															success: function(taluka,status){
																 //
																map.setCenter(new google.maps.LatLng(lat, long)); 
																map.setZoom(8); 
																toggleMarkers();
																// Create markers.
																for (var i = 0; i < taluka.length; i++) {
																	var position =  new google.maps.LatLng(taluka[i].lat, taluka[i].long);
																	var marker = new google.maps.Marker({
																	position: position, 
																	talukaId : taluka[i].id,
																	lat: taluka[i].lat, 
																	long: taluka[i].long, 
																	map: map ,
																	animation: google.maps.Animation.DROP
																  }); 
																  allmarkers.push(marker); 
																  google.maps.event.addListener(marker, 'click', function() {
																		map.panTo(this.getPosition());
																		map.setZoom(9); 
																		map.setMapTypeId(google.maps.MapTypeId.ROADMAP); 
																		var lat = this.lat;
																		var long = this.long;
																		api(lat,long,this.talukaId);		
																		});
																}
																map.addListener('rightclick', function() { 
																toggleMarkers();
																 getstate(); 
																});
																
															}
															});
															//ajax end
														});
														 
												}
												  
												
											}
											});
									});
									 
							}
							
							 
							
						}
						
						});
	 }

	  
	  var latitude ;
	  var longitude ;
	 function api(lat,long,talukaId) {
		  latitude = lat;
		  longitude = long;
		  $.ajax({ 
					 url: MainbaseUrl+"api/getStructures/"+talukaId, 
					 type:'GET',
					 success: function(features,status){
					 
					map.setCenter(new google.maps.LatLng(latitude, longitude)); 
					map.setZoom(8);
					map.setMapTypeId(google.maps.MapTypeId.ROADMAP); 
					toggleMarkers();
					var latlngbounds = new google.maps.LatLngBounds();
					 
					var baseUrl = MainbaseUrl+'images/';
					var icons = {
					  structure: {
						icon: baseUrl +'forestmap.png'
					  },
					  machine: {
						icon: baseUrl +'machine.gif'
					  } 
					};
					
 					// Create markers.
					for (var i = 0; i < features.length; i++) {
						 
					    var myLatlng =  new google.maps.LatLng(features[i].lat, features[i].long);
					    
					    var marker = new google.maps.Marker({ 
					 	position: myLatlng,
						icon: icons[features[i].type].icon ,
						datas : features[i].id,
						lat : features[i].lat,
						long : features[i].long,
						label:features[i].MachineCount,
						map: map,
						animation: google.maps.Animation.DROP
					  });
					    allmarkers.push(marker);
						map.addListener('rightclick', function() {  
						 api(latitude,longitude); 
						});
					    google.maps.event.addListener(marker, 'click', function() {
						
						map.panTo(this.getPosition());
						map.setZoom(9);
						map.setMapTypeId(google.maps.MapTypeId.ROADMAP); 
						
						var lat = this.lat;
						var long = this.long;
						 
						//ajax call for machines
						$.ajax({ 
						url: MainbaseUrl+"api/getmachines/" +this.datas, 
						type:'GET',
						success: function(result,status){
							 //
							map.setCenter(new google.maps.LatLng(lat, long)); 
							map.setZoom(9); 
							toggleMarkers();
							  	 
							//info windows end
							
							var latlngbounds = new google.maps.LatLngBounds();
							// Create markers.
							for (var i = 0; i < result.length; i++) { 
							    var myLatlng =  new google.maps.LatLng(result[i].lat, result[i].long); 
							    var marker = new google.maps.Marker({
								position: myLatlng,
								icon: icons[result[i].type].icon ,
								message: result[i].machinecode,
								map: map ,
								animation: google.maps.Animation.DROP
							  }); 
							  //info windows start
									var content = '<div id="content">'+
								  '<div id="siteNotice">'+
								  '</div>'+
								  '<h1 id="firstHeading" class="firstHeading">'+result[i].machinecode+'</h1>'+
								  '<div id="bodyContent">'+
								  '<p><b>Location : '+result[i].address+'</b></p>'+
								  '<p><b>Status : '+result[i].status+'</b></p>'+
								  '</div>';
									var infowindow = new google.maps.InfoWindow(); 
								   
									google.maps.event.addListener(marker,'click', (function(marker,content,infowindow){ 
										return function() {
										   infowindow.setContent(content);
										   infowindow.open(map,marker);
										};
										map.addListener('rightclick', function() {  
										 api(latitude,longitude); 
										});
									})(marker,content,infowindow)); 
									
							   latlngbounds.extend(marker.position); 
							  //Get the boundaries of the Map.
								var bounds = new google.maps.LatLngBounds();
						 
								//Center map and adjust Zoom based on the position of all markers.
								map.setCenter(latlngbounds.getCenter());
								map.fitBounds(latlngbounds); 
							}
  
						}
						});
						//ajax ends for machine 
						
					  }); 
						latlngbounds.extend(marker.position); 
					} 
						//Get the boundaries of the Map.
						var bounds = new google.maps.LatLngBounds();
				 
						//Center map and adjust Zoom based on the position of all markers.
						map.setCenter(latlngbounds.getCenter());
						map.fitBounds(latlngbounds); 
					  
					}
				});
				//ajax ends for structure 
	};
		
    	function toggleMarkers() {
		  for (i = 0; i < allmarkers.length; i++) {
			if (allmarkers[i].getMap() != null) 
			{
				allmarkers[i].setMap(null);
			}
			else {
				allmarkers[i].setMap(null);
			}
		  }
		}
    </script>
    <script async defer
    src="https://maps.googleapis.com/maps/api/js?key=AIzaSyC2vekUdkuU4BYhb1hVeDvyUy8u_aC-Ids&libraries&callback=getstate">
	//&callback=initMap/api
    </script>
  </body>
</html>
