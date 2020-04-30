
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
<h3 align="center">Patient Details Register</h3>	
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
                  <th class="th-sm">Area

                  </th>
                  <th class="th-sm">Patient Name

                  </th>
                  <th class="th-sm">Patient Mobile Number

                  </th>
                 
                 
                  <th class="th-sm">Address

                  </th>
                  <th class="th-sm">Date Time

                  </th>
                </tr>
              </thead>
              <tbody>
                    @foreach($vehicleDailyRegisterData as $data)
                    <tr>
                      <td>{{$data->patient_area}}</td>
                      <td>{{$data->patient_name}}</td>
                      <td>{{$data->patient_phone}}</td>
                      <td>{{  str_limit($data->patient_address, $limit = 30, $end = '...')}}</td>
                      <td>{{$data->created_datetime}}</td>
                      
                      
                    </tr>
                    @endforeach

              </tbody>
              <tfoot>
                <tr>
                  <th>Area

                  </th>
                  <th>Patient Name

                  </th>
                  <th>Patient Mobile Number

                  </th>
            
                  <th>Address

                  </th>
                  <th>Date

                  </th>
                </tr>
              </tfoot>
            </table>  
        </div>
    </div>
</div>
</body>
 
</html>
