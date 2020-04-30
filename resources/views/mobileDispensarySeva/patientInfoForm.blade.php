
<html>
 
<head>
 
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.0/jquery.min.js"></script>
    <link rel="stylesheet" href="//netdna.bootstrapcdn.com/bootstrap/3.1.1/css/bootstrap.min.css">
    <script src="//netdna.bootstrapcdn.com/bootstrap/3.1.1/js/bootstrap.min.js"></script>
    <meta charset="utf-8">
   <style type="text/css">
    #myOverlay{
		position:absolute;height:100%;width:100%;
	}
	#myOverlay{background:black;opacity:.7;z-index:2;display:none;}

	#loadingGIF{position:absolute;top:60%;left:25%;z-index:3;display:none;}

	button{margin:50px;height:60px;width:100px;}
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

    $(document).ready(function(){ 
        $("#Other").hide();
        $(".loader").hide();
        
       
        
        $('#doctor_org').on('change', function() {
    
      if ( $('#doctor_org').val() != 'Other' ) 
        {
            $("#Other").hide();
        } 
        
      if ( $('#doctor_org').val() == 'Other' ) 
        {
            $("#Other").show(); 
        }    
      });
    
	
	$("form").submit(function(){
		// $("#button").click(function() {
		$('#myOverlay').show();
		$('#loadingGIF').show();
		// setTimeout(function(){
			// $('#myOverlay').hide();
			// $('#loadingGIF').hide();
		// },3000);
	// });
  });
  
	}); 
    
	
	// $('form').on('blur', 'input', function(event) {
     // $('form').trigger('submit');
	// });
	
	// function blur()
	// {
		// alert('submit');
		
	// }
	
    function createCookie(cookieName,cookieValue,hoursToExpire)
        {
          var date = new Date();
          //date.setTime(date.getTime()+(daysToExpire*24*60*60*1000));
          date.setTime(date.getTime()+(hoursToExpire*60*60*1000));
          document.cookie = cookieName + "=" + cookieValue + "; expires=" + date.toGMTString();
        }
    function accessCookie(cookieName)
        {
          var name = cookieName + "=";
          var allCookieArray = document.cookie.split(';');
          for(var i=0; i<allCookieArray.length; i++)
          {
            var temp = allCookieArray[i].trim();
            if (temp.indexOf(name)==0)
            return temp.substring(name.length,temp.length);
          }
          return "";
        }
    function checkCookie()
        {
          var vanCode = accessCookie("vanCode");
          var currentVanCode = <?php echo json_encode($vanCode??''); ?>; //
          if(currentVanCode != '' && vanCode !=currentVanCode)
          {
            

            currentvanCodeArr = currentVanCode.split("_");
            console.log(currentvanCodeArr);
            document.getElementById('vanCode').value =currentvanCodeArr[0]+'('+currentvanCodeArr[1]+')';
           

            document.cookie = currentVanCode + '=; expires=Thu, 01-Jan-70 00:00:01 GMT;';
                
                createCookie("vanCode", currentVanCode, 12);


          }else if( vanCode !='' )
          {
            
            vanCodeArr = vanCode.split("_");
            
            document.getElementById('vanCode').value =vanCodeArr[0]+'('+vanCodeArr[1]+')';

          }else if(vanCode == '' && currentVanCode == '') {
            
            document.getElementById('showMsg').innerHTML = 'Please fill Daily Vehicle Sheet.';
          }
         
         
        }

        function backButtonfunction()
        {
            window.location.href = "http://13.235.105.204/api/selectVan";
        }

        //$("#flashmsg").hide("slow");
        setTimeout(function() {
            $('#flashmsg').fadeOut('slow');
        }, 6000); 

        setTimeout(function() {
            $('#errMsg').fadeOut('slow');
        }, 6000);
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
						<div id="errMsg" class="alert-warning">
						{{$errMsg ?? ""}}
                        </div>
                        <div id="flashmsg" class="alert-success">{{$msg ?? ""}}</div>
						<!-- loader start-->
							<div id="myOverlay"></div>
						<div id="loadingGIF"><img src="https://i.ya-webdesign.com/images/gif-loading-png-8.gif"/></div>
							<!-- loader End-->
                        <div  class="form-group">
                            <input type="button" class="btn btn-success" value="Back" onclick="backButtonfunction()"/>
                        </div>
                        <h3>Van - Area Wise Patients Register</h3>
                    <form onsubmit="return validate()" action="http://13.235.105.204/api/savePatientInfo" method="post" enctype="multipart/form-data">
                          <div id="container" style="display:none;width:5px;height:5px">

<img id="source_image"><img id="compressed_image">

						  </div>

                        <legend></legend>

                            
                             <!--div class="form-group">
                                 <label for="patient_area">Van Code </label>
                                <input type="text" id="vanCode" name="vanCode" placeholder="vanCode" class="form-control"  readonly="readonly"  required = "required" value="" }} />
                                     
                                
                             </div-->
                             <div class="form-group">
                                <label for="vanCode">Select Van / वैन का चयन करें </label>
                                 <select class="form-control" name="vanCode" id="vanCode"  required = "required" oninvalid="this.setCustomValidity('Please Select Van From the List')" oninput="setCustomValidity('')">
                                     <option value="">---------Choose Van---------</option>
                                      @foreach($vanData as $data)
                                        <option value="{{$data->bjs_vehicle_no}}_{{$data->vehicle_reg_no}}">{{ $data->bjs_vehicle_no}}({{$data->vehicle_reg_no}})</option>
                                    @endforeach
                                    
                                     
                                 </select>    
                                
                             </div>
                             <div class="form-group">
                                 <label for="patient_area">Area / स्थान</label>
                                 <input type="read" name="patient_area" placeholder="Pimple Gurav" class="form-control" required = "required" pattern="[a-zA-Z][a-zA-Z0-9\s]*" oninvalid="this.setCustomValidity('Please Enter Valid Area')" oninput="setCustomValidity('')"/>
                                
                             </div>
                             <div class="form-group">
                                 <label for="doctor_org">Doctor supported by / डॉक्टर किसके द्वारा दिया गया </label>
                                 <select class="form-control" required = "required" name="doctor_org" id="doctor_org"  oninvalid="this.setCustomValidity('Please Select Docter Supported by From the List')" oninput="setCustomValidity('')">
                                     <option value="">---------Choose--------</option>
                                     <option value="BJS">BJS</option>
                                     <option value="Other">Other</option>
                                 </select>  
                             </div>
                             
                             <div class="form-group" id="Other" >
                                 <label for="doctor_name">Other / अन्य</label>
                                 <input type="text"  name="Other" placeholder="Other" class="form-control" oninvalid="this.setCustomValidity('Please Enter Other Company Name')" oninput="setCustomValidity('')"/>
                             </div>
                             <div class="form-group" id="DoctorName" >
                                 <label for="doctor_name">Doctor Name / डॉक्टर का नाम</label>
                                 <input type="text" required = "required" name="doctor_name" placeholder="Doctor Name" class="form-control"  oninvalid="this.setCustomValidity('Please Enter Valid Doctor Name')" oninput="setCustomValidity('')"/>
                             </div>
                             <div class="form-group" id="DoctorMobileNo" >
                                 <label for="doctor_phone">Doctor Mobile No. / डॉक्टर का मोबाइल नं.</label>
                                 <input type="text" required = "required"  name="doctor_phone" placeholder="Doctor Mobile no." class="form-control"pattern= "[0-9]+" minlength="10" maxlength="10" oninvalid="this.setCustomValidity('Please Enter Valid Doctor Mobile Number')" oninput="setCustomValidity('')"/>
                             </div>
                             <div class="form-group">
                                 <label for="patient_phone">Total count of patients / रोगियों की कुल गिनती</label>
                                 <input type="number" name="patients_count" placeholder="Total count of patients" class="form-control" required = "required" pattern= "[0-9]+" minlength="1" maxlength="4" oninvalid="this.setCustomValidity('Please Enter Valid Count of Patients')" oninput="setCustomValidity('')"/>
                                
                             </div>

                             <div class="form-group">
                                 <label for="patient_phone">Total patients asked to isolate / 
कुल कितने रोगियों को अलग करने के लिए कहा</label>
                                 <input type="number" name="isolated_patients_count" placeholder="Total patients asked to isolate" class="form-control" required = "required" pattern= "[0-9]+" minlength="1" maxlength="4" oninvalid="this.setCustomValidity('Please Enter Valid Mocount of patients')" oninput="setCustomValidity('')"/>
                                
                             </div>
                             <div class="form-group">
                                 <label for="patient_phone">Total patients referred to Govt. hospitals / 
कुल कितने मरीजों को सरकारी अस्पतालों में भेजा गया</label>
                                 <input type="number" name="patients_referred_govt_hospital_count" placeholder="Total patients referred to Govt. hospitals" class="form-control" required = "required" pattern= "[0-9]+" minlength="1" maxlength="4" oninvalid="this.setCustomValidity('Please Enter Valid Mocount of patients')" oninput="setCustomValidity('')"/>
                                
                             </div>
                             <div class="form-group">
                                 <label for="vehicle_reg_no">Upload register image 1 / रजिस्टर फ़ोटो अपलोड करें 1</label>
                                 <input type="file" id="register_images_one" name="register_images_one" class="form-control"  />
                                
                             </div>

                             <div class="form-group">
                                 <label for="vehicle_reg_no">Upload register image 2 / रजिस्टर  फ़ोटो अपलोड करें 2</label>
                                 <input type="file" id="register_images_two" name="register_images_two" class="form-control" />
                                
                             </div>
							 
						  <div class="form-group">
                                 <label for="vehicle_reg_no">Upload register image 3 / रजिस्टर  फ़ोटो अपलोड करें 3</label>
                                 <input type="file" id="register_images_three" name="register_images_three" class="form-control"/>
                                
                             </div>

                             <div class="form-group">
                                 <label for="vehicle_reg_no">Upload register image 4 / रजिस्टर  फ़ोटो अपलोड करें 4</label>
                                 <input type="file" id="register_images_four" name="register_images_four" class="form-control"/>
                                
                             </div>

                             <div class="form-group">
                                 <label for="vehicle_reg_no">Upload register image 5/ रजिस्टर  फ़ोटो अपलोड करें 5</label>
                                 <input type="file" id="register_images_five" name="register_images_five" class="form-control"/>
                                
                             </div>
                            <br/>
                            <div id="levelContainer"  class="form-group">
                                   
                            </div>
							
                            <input type="submit" id="submit" class="btn btn-success"/>
                         </form>                        
                </div>
            </div>
        </div>
    </div>
</div>
</body>
 

 <script type="text/javascript">
 
 function validate() {
document.getElementById("register_images_one").value = '';
document.getElementById("register_images_two").value = '';
document.getElementById("register_images_three").value = '';
document.getElementById("register_images_four").value = '';
document.getElementById("register_images_five").value = '';
$('#myOverlay').show();
$('#loadingGIF').show();
		
}	
document.getElementById("register_images_one").addEventListener("change", readFile, false);
document.getElementById("register_images_two").addEventListener("change", readFile, false);
document.getElementById("register_images_three").addEventListener("change", readFile, false);
document.getElementById("register_images_four").addEventListener("change", readFile, false);
document.getElementById("register_images_five").addEventListener("change", readFile, false);

var output_format = null;
	var file_name = null;
	  function readFile(evt) {
		      $('#myOverlay').show();
		$('#loadingGIF').show();
		

		var file = evt.target.files[0];
		var reader = new FileReader();
	    reader.onload = function(event) {
		/*	var image = new Image();
    image.src = event.target.result;
var ww = '';
			  image.onload = function(){
	                
	               // console.log("Image loaded");        
					//console.log(this.width+'******');
					ww = this.width;
	            }*/
					                //console.log("Image loaded"+ww);       
//									console.log(this.width);

			/*var img = document.createElement("IMG");
	            img.src = event.target.result;
				img.setAttribute("src", event.target.result);
				img.setAttribute("id", "source_imagei");
            */
				 /*var source_image = document.getElementById("source_image");
				 source_image.src = event.target.result;
   
	            source_image.onload = function(){
	                
	                console.log("Image loaded");
	            }*/
				
				var i = document.getElementById("source_image");
			//console.log(i);
	            i.src = event.target.result;
				
				 i.onload = function(){
	                
	                console.log("Image loaded");         
					
					
					
					
					
					
					
        var quality = 30;
       	
        console.log("process start...");
        console.log("process start compress ...");
				output_format = file.name.split(".").pop();

		var srcCompressed = jic.compress(source_image,quality,output_format,i.width,i.height).src;
		
	//	console.log(srcCompressed+'----+++++');
		var img = document.createElement("IMG");
		//img.src = event.target.result;
		img.setAttribute("id", "compressed_imagei");
	    
		img.setAttribute("src", srcCompressed);
		document.getElementById('container').appendChild(img);            

		var compressed_image = document.getElementById("compressed_image");
				compressed_image.src = srcCompressed;

        if (compressed_image.src == "") {
            alert("You must compress image first!");
            return false;
        }
		
	    var successCallback= function(response){
          //  console.log("image uploaded successfully! :)");
			
			var x = document.createElement("INPUT");
			x.setAttribute("type", "text");
			x.setAttribute("id", evt.target.id+'_1');
			x.setAttribute("name", evt.target.id+'_1');
			x.setAttribute("value", response);			
			document.getElementById('container').appendChild(x);		
            console.log(response);  
$('#myOverlay').hide();
		$('#loadingGIF').hide();
					
        }

        var errorCallback= function(response){
            console.log("image Filed to upload! :)");
            console.log(response); 
        }
		    	//console.log("---start upload ...");
		file_name = file.name;
		
		
    	jic.upload(compressed_image, "imageSevaUpload", "file", file_name,successCallback,errorCallback);
    	
    	console.log("process start upload ...");
					
					
					
					//console.log(this.width+'******');
					//ww = this.width;
	            }
			
	          
				console.log(i.width+'---width');
				
				//document.getElementById('container').appendChild(img);
			//	console.log('sorce file'+event.target.result)
				
				     if (i.src == "") {
            alert("You must load an image first!");
            //return false;
        }

        /*var quality = 30;
       	
        console.log("process start...");
        console.log("process start compress ...");
				output_format = file.name.split(".").pop();

		var srcCompressed = jic.compress(source_image,quality,output_format,i.width,i.height).src;
		
	//	console.log(srcCompressed+'----+++++');
		var img = document.createElement("IMG");
		//img.src = event.target.result;
		img.setAttribute("id", "compressed_imagei");
	    
		img.setAttribute("src", srcCompressed);
		document.getElementById('container').appendChild(img);            

		/*var compressed_image = document.getElementById("compressed_image");
				compressed_image.src = srcCompressed;

        if (compressed_image.src == "") {
            alert("You must compress image first!");
            return false;
        }*/
		
	 /*   var successCallback= function(response){
          //  console.log("image uploaded successfully! :)");
			
			var x = document.createElement("INPUT");
			x.setAttribute("type", "text");
			x.setAttribute("id", evt.target.id+'_1');
			x.setAttribute("name", evt.target.id+'_1');
			x.setAttribute("value", response);			
			document.getElementById('container').appendChild(x);		
            console.log(response);       
        }

        var errorCallback= function(response){
            console.log("image Filed to upload! :)");
            console.log(response); 
        }
		    	//console.log("---start upload ...");
		file_name = file.name;

    	jic.upload(compressed_image, "imageSevaUpload", "file", file_name,successCallback,errorCallback);
    	
    	console.log("process start upload ...");*/
    	
		

	    };
		output_format = file.name.split(".").pop();
	    console.log("Filename:" + file.name);
	    console.log("Fileformat:" + output_format);
	    console.log("Filesize:" + (parseInt(file.size) / 1024) + " Kb");
	    console.log("Type:" + file.type);
	    reader.readAsDataURL(file);
		
		
		//$("#compress").show();
	    return false;
	}

 </script>
 	<script src="/js/JIC.js"></script>

</html>
