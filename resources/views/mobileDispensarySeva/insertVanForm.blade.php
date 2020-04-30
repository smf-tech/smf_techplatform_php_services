
<html>
 
<head>
 
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.0/jquery.min.js"></script>
    <link rel="stylesheet" href="//netdna.bootstrapcdn.com/bootstrap/3.1.1/css/bootstrap.min.css">
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

        #flashmsg {
            color: 
        }

    /* .... Other css rules here ... */

</style>
<script type="text/javascript">
    
 function backButtonfunction()
        {
            window.location.href = "http://13.235.105.204/api/webOptionView";
        }  

</script>
</head>
<body >
<div class="container">
    
    <div class="row">
        <div class="col-md-8 col-md-offset-2">
            <div class="panel panel-default">

                <div class="panel-body">
                        <div id="showMsg" class="alert-warning">
        
                        </div>
                        <div id="flashmsg" class="alert-success">{{$msg ?? ""}}</div>
                        <div  class="form-group">
                            <input type="button" class="btn btn-success" value="Back" onclick="backButtonfunction()"/>
                        </div>
                        <h3>Insert vehicle information</h3>
                    <form action="http://13.235.105.204/api/insertVanInfo" method="post">
                          
                        <legend></legend>

                            
                            <div class="form-group">
                                 <label for="patient_area">Volunteers Name </label>
                                <input type="text" id="volunteers_name" name="volunteers_name" placeholder="Volunteers Name" class="form-control" required = "required" pattern="[a-zA-Z][a-zA-Z0-9\s]*" oninvalid="this.setCustomValidity('Please Enter Valid Volunteers Name')" oninput="setCustomValidity('')"/>
                             </div>
                             
                             <div class="form-group">
                                 <label for="volunteers_mobile_number">Volunteers Mobile Number</label>
                                 <input type="text" name="volunteers_mobile_number" placeholder="Volunteers Mobile Number" class="form-control" required = "required" pattern= "[0-9]+" minlength="10" maxlength="10" oninvalid="this.setCustomValidity('Please Enter Valid Volunteers Mobile Number')" oninput="setCustomValidity('')"/>
                                
                             </div>
                             <div class="form-group">
                                 <label for="vehicle_owners_name">Vehicle Owners Name </label>
                                <input type="text" id="vehicle_owners_name" name="vehicle_owners_name" placeholder="Vehicle Owners Mobile Number" class="form-control" required = "required" pattern="[a-zA-Z][a-zA-Z0-9\s]*" oninvalid="this.setCustomValidity('Please Enter Valid Vehicle owners Name')" oninput="setCustomValidity('')"/>
                             </div>

                             <div class="form-group">
                                 <label for="vehicle_owners_mobile_number"> Vehicle Owners Mobile Number </label>
                                <input type="text" id="vehicle_owners_mobile_number" name="vehicle_owners_mobile_number" placeholder="Vehicle Owners Mobile Number" class="form-control" required = "required" pattern= "[0-9]+" minlength="10" maxlength="10" oninvalid="this.setCustomValidity('Please Enter Valid owner Mobile Number')" oninput="setCustomValidity('')"/>
                             </div>

                             <div class="form-group">
                                 <label for="vehicle_owners_pan_number">Vehicle Owners Pan Number</label>
                                 <input type="text" name="vehicle_owners_pan_number" placeholder="Vehicle Owners Pan Number" class="form-control" minlength="10" maxlength="10" />
                                
                             </div>
                             
                             <div class="form-group">
                                 <label for="vehicle_reg_no">Vehicle Registration No.</label>
                                 <input type="text" name="vehicle_reg_no" placeholder="MH-XX-XX-XXXX" class="form-control" required = "required" />
                                
                             </div>

                             <div class="form-group">
                                 <label for="vehicle_city">Select Van City </label>
                                <!--input type="text" id="vehicle_city" name="vehicle_city" placeholder="Van City" class="form-control" pattern="[a-zA-Z][a-zA-Z0-9\s]*" oninvalid="this.setCustomValidity('Please Enter Valid Van City')" oninput="setCustomValidity('')"/-->

                                <select class="form-control" name="vehicle_city" id="vehicle_city"  required = "required" oninvalid="this.setCustomValidity('Please Select city From the List')" oninput="setCustomValidity('')">
                                     <option value="">---------Choose City---------</option>
                                      @foreach($cityData as $city)
                                        <option value="{{$city->city_name}}">{{$city->city_name}}</option>
                                    @endforeach
                                        <!--option value="NA">NA</option-->
                                 </select>    
                             </div>

                             <!--div class="form-group">
                                 <label for="vehicle_city">Select Van Depot </label>
                                

                                <select class="form-control" name="vehicle_depot" id="vehicle_depot" >
                                     <option value="">---------Choose Depot---------</option>
                                      @foreach($depotData as $depot)
                                        <option value="{{$depot->depot_name}}">{{$depot->depot_name}}</option>
                                    @endforeach
                                        <option value="NA">NA</option>
                                 </select>    
                             </div-->

                             <!--div class="form-group">
                                 <label for="vehicle_reg_no">Take image</label>
                                 <input type="file"  accept="image/* capture="camera"name="test_image class="form-control" required = "required" />
                                
                             </div-->

                             

                       
                            <br/>
                            <input type="submit" class="btn btn-success"/>
                         </form>                        
                </div>
            </div>
        </div>
    </div>
</div>
</body>
 
</html>
