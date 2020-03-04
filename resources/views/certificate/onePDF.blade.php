<!doctype html>
<html lang="en">
<head>

    <meta http-equiv="Content-Type" content="text/html" charset="UTF-8">
    <title> @if(isset($UserData['certificateType']))
                    {{$UserData['certificateType']}}
                @endif </title>
    <link href='http://fonts.googleapis.com/css?family=Roboto' rel='stylesheet'>

<style type="text/css">
        @import url('https://fonts.googleapis.com/css?family=Hind&display=swap');
        body,html{padding:0; margin:0.0rem;height:100%;width:100%;}
        body{
            background: url('http://13.235.105.204/images/2019_MV_Certificate01.png');
            font-family: 'Roboto' !important;
           /*font-family: 'Mukta', sans-serif;
            font-family: 'Hind', sans-serif;*/
            font-size: 0.9em !important;
            line-height: 1.15em !important;

        }


           

            .content{
                width: 100%;
                position: absolute;
                
               
               
               
            }

         .userName
            {
                font-family: 'Roboto' !important;
                /*font-family: 'Hind', sans-serif;*/
                font-size: 1.2em !important;
                line-height: 1.5em !important;
                margin-left:1%;
                position: absolute;
                top:46%;
                left:38%; 
               
            }

            .schoolName
            {
                font-family: 'Roboto' !important;
                /*font-family: 'Hind', sans-serif;*/
                font-size: 1.2em !important;
                line-height: 1.5em !important;
                margin-left:1%;
                position: absolute;
                top:50%;
                left:17%; 
               
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
    {{ $UserData['userName']}}              </div>   
    <div class="schoolName">
        {{ $UserData['schoolName']}}
    </div>
        
         
        
    @endif  
     
</div>






</body>
</html>