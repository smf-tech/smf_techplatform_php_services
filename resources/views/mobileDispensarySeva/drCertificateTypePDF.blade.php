<!doctype html>
<html lang="">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />  
    <title> @if(isset($UserData['certificateType']))
                    {{$UserData['certificateType']}}
                @endif </title> 
<style type="text/css">
        @font-face {
            font-family: 'Noto Sans', sans-serif !important;
            font-weight: normal;
            src: url('http://the-octopus.com/fonts/NotoSansDevanagari-Regular.ttf') format('truetype');
        }
        
        body,html{padding:0; margin:0.0rem;height:100%;width:100%;}
        body{
            background: url('http://the-octopus.com/images/doctorCertificate.jpg');
            font-family: 'Noto Sans', sans-serif !important; 
            font-size: 0.9em !important;
            line-height: 1.15em !important; 
        }
 
            .content{
                width: 100%;
                position: absolute;
                
            } 
         .userName
            {
                font-size: 1.5em !important;
                line-height: 1.5em !important;
                margin-left:1%;
                position: absolute;
                top:36.3%;
                left:22%; 
               
            }

            .days
            {
                font-size: 1.5em !important;
                line-height: 1.5em !important;
                margin-left:1%;
                position: absolute;
                top:45%;
                left:29.5%; 
               
            }

            .district
            {
                font-size: 1.2em !important;
                line-height: 1.5em !important;
                margin-left:1%;
                position: absolute;
                top:49.5%;
                left:53.3%; 
               
            }


            .taluka
            {
                font-size: 1.2em !important;
                line-height: 1.5em !important;
                margin-left:1%;
                position: absolute;
                top:49.5%;
                left:24%; 
               
            }
            .page-break {
                page-break-after: always;
                }
    </style>

</head>
<body>



<div class="content">

    @if(isset($UserData)) 

    <div class="userName" >
        {{ $UserData['userName']}}              
    </div>   
    <div class="days">
        {{ $UserData['totalWorkingDays']}}
    </div>

    <!-- <div class="district">
        {{ $UserData['district']}}
    </div>


    <div class="taluka">
        {{ $UserData['taluka']}}
    </div> -->

    <div>
        
    </div>
    <div>

    </div>
        
         
        
    @endif  
     
</div>






</body>
</html>