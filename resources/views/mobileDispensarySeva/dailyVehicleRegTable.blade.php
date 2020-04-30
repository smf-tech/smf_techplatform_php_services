
<html>
 
<head>
 
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.0/jquery.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.3.1.js"></script> 
    <script src="https://cdn.datatables.net/1.10.20/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.10.20/js/dataTables.bootstrap.min.js"></script>
    <link rel="stylesheet" href="//netdna.bootstrapcdn.com/bootstrap/3.1.1/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">

    <script src="//netdna.bootstrapcdn.com/bootstrap/3.1.1/js/bootstrap.min.js"></script>
    <meta charset="utf-8">
   <style type="text/css">
   	@font-face {
           
            font-family: 'Noto Sans', sans-serif !important;
            font-weight: normal;
            src: url('http://13.235.105.204/fonts/NotoSans-Regular.ttf') format('truetype');
        }
     
       

       body,html{padding:0; margin:0.0rem;height:100%;width:100%;}
        body{
           /* background: url('http://13.235.105.204/images/2019_MV_Certificates_11_12_2019_Final_Prerak.png');*/
            font-family: 'Noto Sans', sans-serif !important; 
            font-size: 2em !important;
            line-height: 2em !important; 
        }

    /* .... Other css rules here ... */

</style>
<script>
    

     $(document).ready(function() {
        $('#example').DataTable();
    } );    
     
    function backButtonfunction()
        {
            window.location.href = "http://13.235.105.204/api/webOptionView";
        } 
	
   
</script>
</head>
<body>
<div class="container">

<div class="row" >
<h3 align="center">Van - Area Visit Register</h3>	
</div>
<br>
    <!--div class="row" style="margin-left: 25%;" id="buttonRow">
         <div class="col-md-3 col-md-offset-2">
			<div  class="form-group">
				<input type="button" class="btn-lg btn-success" value="Daily Vehicle Sheet" onclick="vehicleSheetfunction()" />
            </div>
			
		</div>
		<div class="col-md-3">
			<div  class="form-group">
				<input type="button" class="btn-lg btn-success" id='patienRegBtn' value="Patients Register" onclick="PatientsRegisterfunction()"/>
            </div>
			
		</div>
	</div-->
    <div class="row">
        <div class="col-sm-2">
            
            <div  class="form-group">
	        <input type="button" class="btn btn-success" value="Back" onclick="backButtonfunction()"/>
	        </div>
        </div>
    </div>	
   <div class="row" id="">
        <div class="">
            <table id="example" class="datatable table table-striped table-bordered" style="width:100%">
              <thead>
                <tr>
                  <th class="th-sm">Van Registration No.

                  </th>
                  <th class="th-sm">Driver Name

                  </th>
                  <th class="th-sm">Driver Mobile Number

                  </th>
                  <th class="th-sm">Day Start Time

                  </th>
                  <th class="th-sm">Day Start Location</th>
                  <th class="th-sm">Start Meter Reading</th>
                  <th class="th-sm">Visited Area 1

                  </th>
                  <th class="th-sm">Area 1 Visit Duration(Hours)

                  </th>
                  <th class="th-sm">Visited Area 2

                  </th>
                  <th class="th-sm">Area 2 Visit Duration(Hours)

                  </th>
                  <th class="th-sm">Visited Area 3

                  </th>
                  <th class="th-sm">Area 3 Visit Duration(Hours)

                  </th>
                  <th class="th-sm">Visited Area 4

                  </th>
                  <th class="th-sm">Area 4 Visit Duration(Hours)

                  </th>



                  <th class="th-sm">Day End Location

                  </th>
                 
                  <th class="th-sm">End Meter Reading

                  </th>
                  <th class="th-sm">Date Time

                  </th>
                </tr>
              </thead>
              <tbody>
                    @foreach($vehicleDailyRegisterData as $data)
                    <tr>
                      <td>{{$data->vehicle_reg_no}}</td>
                      <td>{{$data->driver_name}}</td>
                      <td>{{$data->driver_phone}}</td>
                      <td>{{$data->day_start_time}}</td>
                      <td>{{$data->start_location}}</td>
                      <td>{{$data->start_meter_reading}}</td>
                      <td>{{$data->visited_area_1}}</td>
                      <td>{{$data->area_1_visit_duration}}</td>
                      <td>{{$data->visited_area_2}}</td>
                      <td>{{$data->area_2_visit_duration}}</td>
                      <td>{{$data->visited_area_3}}</td>
                      <td>{{$data->area_3_visit_duration}}</td>
                      <td>{{$data->visited_area_4}}</td>
                      <td>{{$data->area_4_visit_duration}}</td>
                      <td>{{$data->end_location}}</td>
                      <td>{{$data->end_meter_reading}}</td>
                      <td>{{$data->created_datetime}}</td>
                      
                    </tr>
                    @endforeach

              </tbody>
              <tfoot>
                <tr>
                  <th>Van Registration No.

                  </th>
                  <th>Driver Name

                  </th>
                  <th>Driver Mobile Number

                  </th>
                  <th>Day Start Time

                  </th>
                  <th>Day Start Location</th>
                  <th>Start Meter Reading</th>
                  <th>Visited Area 1

                  </th>
                  <th>Area 1 Visit Duration(Hours)

                  </th>
                  <th>Visited Area 2

                  </th>
                  <th>Area 2 Visit Duration(Hours)

                  </th>
                  <th>Visited Area 3

                  </th>
                  <th>Area 3 Visit Duration(Hours)

                  </th>
                  <th>Visited Area 4

                  </th>
                  <th>Area 4 Visit Duration(Hours)

                  </th>



                  <th>Day End Location

                  </th>
                 
                  <th>End Meter Reading

                  </th>
                  <th>Date Time

                  </th>
                </tr>
              </tfoot>
            </table>  
        </div>
    </div>
</div>
</body>
 
</html>
