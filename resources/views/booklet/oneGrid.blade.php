<!doctype html>
<html lang="en">
<head>

    <meta charset="UTF-8">
    <title>Meet PDF :Four </title>
    <link href='http://fonts.googleapis.com/css?family=Roboto' rel='stylesheet'>
<style type="text/css">
        body,html{padding:0; margin:1.5rem;height:100%;width:100%;}

        body{
  
            font-family: 'Roboto' !important;
            font-size: 0.9em !important;
            line-height: 1.15em !important;

        }


           

            .content{
                width: 100%;
                position: relative;

               
               
               
            }

            .part1 td{
                vertical-align: top !important;

            }
            .part1 tr{
                /*line-height: 40px;*/

            }
            .main_table{
               
                
                 border:   0px solid green;
            }

            .seprator{
            width: 5px !important;
            }


                .userName_header
                {
                    text-align: center;
                    padding: 1.0rem 0rem 0.9rem 0rem;
                    margin-left:50px;
                    width:100%;
                    font-family: 'Roboto' !important;
                    font-size: 1.2em !important;
                    line-height: 1.8em !important;      
                    color: #000000;
                    background-color: #d5d5d5;
                    /*position: relative;*/  
                    border-radius: 25px;
                    margin: 1%;

                }

                
                .userName
                {
                   
                    background-color: #d5d5d5;
                   
                }
                .rightText
                    {
                        font-family: 'Roboto' !important;
                        font-size: 1.2em !important;
                        line-height: 1.8em !important;
                        margin-left:1%;
                        background-color: #d5d5d5;
                        padding: 2% 6%;
                        border-radius: 25px;
                       
                    }
                .rightText tr {

                        padding: 5em 5em !important;
                        margin: 5em 5em !important;

                }    
                .lableText
                {
                    color: #1b6ad5; 
                    font-family: 'Roboto' !important;
                    font-size: 1.0em !important;
                    line-height: 1.8em !important;
                    margin-left:1%; 
                }

                .infoText
                {
                    font-family: 'Roboto' !important;
                    font-size: 0.9em !important;
                    line-height: 1.5em !important;
                    margin-left:1%;
                    border-radius: 25px; 
                    
                    background: #ffffff !important;
                    padding: 20px;
                    margin: 20px; 
                   
                }     
            img{
               
             
                width: 850px;
                height: 1080px;
                border:2px solid #fff;
                border-radius:17px;
                margin-bottom:72px;
                border-radius: 50px;
                padding-right:  2%;
                background-color: #d5d5d5;
            }
            .page-break {
                page-break-after: always;
                }
    </style>

</head>
<body>


<div class="content">
    <div class="userName_header">
        <span class="leftText">
            <strong>
                @if(isset($userData['meet_title']))
                    {{$userData['meet_title']}}
                @endif
            </strong>
        </span>
    </div>   
</div>
<div class="content">

    @if(isset($userData['male']))    
        @foreach($userData['male'] as $item) 
            <table class="main_table"  width="100%" border="0">

                <tr>
                    <td>
                        <table border="0" width="100%"  >
                               
                                <tr>
                                    <td width="35%" rowspan="0"  align="center" valign="top" >
                                          @if($item['other_marital_information']['profile_image'])
                                                <img src="{{$item['profileImage']}}"> 
                                          @else 
                                                 <img src="{{ base_path() }}/resources/assets/images/profile.png" />
                                                
                                            @endif
                                    </td>
                                    <td>&nbsp;</td>
                                    <td width="58%" valign="top" >
                                        <table width="100%"  class="rightText" border="0" >
                                            <tr>
                                                <td width="39%" class="lableText">Name</td>
                                                <td width="1%">:</td>
                                                <td width="60%">
                                                   <div class="infoText" >
                                                 {{ $item['personal_details']['first_name'] }}
                                                    {{ $item['personal_details']['middle_name'] }}

                                                    {{ $item['personal_details']['last_name'] }}                       </div>                        
                                                </td>

                                            </tr>
                                                <td width="39%" class="lableText">Badge No.</td>
                                                <td width="1%">:</td>
                                                <td>
                                                     <div class="infoText" >
                                                        {{ $item['badge']??'-'}}
                                                     </div>
                                                </td>
                                            </tr>
                                            
                                            <tr>
                                                <td width="39%" class="lableText">Birth Date</td>
                                               <td width="1%">:</td>
                                                <td width="60%">
                                                    <div class="infoText"> 
                                                    {{ $item['birthDate'] }}
                                                    </div>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td width="39%" class="lableText">Height</td>
                                                <td width="1%">:</td>
                                                <td width="60%">
                                                    <div class="infoText">
                                                        {{ $item['personal_details']['height'] }}
                                                    </div>    
                                                </td>
                                            </tr>
                                            <tr>
                                                <td width="39%" class="lableText">Education</td>
                                                <td width="1%">:</td>
                                                <td width="60%">
                                                    <div class="infoText" >
                                                    {{   $item['educational_details']['education_level'] }},
                                                    {{$item['educational_details']['qualification_degree']}}     
                                                    </div>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td width="39%" class="lableText">Profession</td>
                                                <td width="1%">:</td>
                                                <td width="60%">
                                                    <div class="infoText">
                                                        {{$item['occupational_details']['occupation']}}
                                                    </div>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td width="39%" class="lableText">Jain Sect</td>
                                                <td width="1%">:</td>
                                                <td width="60%">
                                                   <div class="infoText">     
                                                    {{$item['personal_details']['sect']}}
                                                   </div>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td width="39%" class="lableText">Parent City</td>
                                                <td width="1%">:</td>
                                                <td width="60%">
                                                    <div class="infoText">
                                                        {{ $item['residential_details']['address'] }}
                                                    </div>
                                                </td>
                                            </tr>


                                            <tr>
                                                <td width="39%" class="lableText">Candidate City</td>
                                                <td width="1%">:</td>
                                                <td width="60%">
                                                   <div class="infoText" >  
                                                    {{ $item['residential_details']['address'] }}
                                                   </div>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                          
                                </tr>
         
                        </table>
                    </td>
                </tr>
             </table>
         <div class="page-break"></div>
         @endforeach
    @endif  
     
</div>


<div class="content">

    @if(isset($userData['female']))    
        @foreach($userData['female'] as $item) 
            <table class="main_table"  width="100%" border="0">

                <tr>
                    <td>
                        <table border="0" width="100%"  >
                               
                                <tr>
                                    <td width="35%" rowspan="0"  align="center" valign="top" >
                                          @if($item['other_marital_information']['profile_image'])
                                                <img src="{{$item['profileImage']}}"> 
                                          @else 
                                                 <img src="{{ base_path() }}/resources/assets/images/profile.png" />
                                                
                                            @endif
                                    </td>
                                    <td>&nbsp;</td>
                                    <td width="58%" valign="top" >
                                        <table width="100%"  class="rightText" border="0" >
                                            <tr>
                                                <td width="39%" class="lableText">Name</td>
                                                <td width="1%">:</td>
                                                <td width="60%">
                                                   <div class="infoText" >
                                                 {{ $item['personal_details']['first_name'] }}
                                                    {{ $item['personal_details']['middle_name'] }}

                                                    {{ $item['personal_details']['last_name'] }}                       </div>                        
                                                </td>

                                            </tr>
                                                <td width="39%" class="lableText">Badge No.</td>
                                                <td width="1%">:</td>
                                                <td>
                                                     <div class="infoText" >
                                                        {{ $item['badge']??'-'}}
                                                     </div>
                                                </td>
                                            </tr>
                                            
                                            <tr>
                                                <td width="39%" class="lableText">Birth Date</td>
                                               <td width="1%">:</td>
                                                <td width="60%">
                                                    <div class="infoText"> 
                                                    {{ $item['birthDate'] }}
                                                    </div>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td width="39%" class="lableText">Height</td>
                                                <td width="1%">:</td>
                                                <td width="60%">
                                                    <div class="infoText">
                                                        {{ $item['personal_details']['height'] }}
                                                    </div>    
                                                </td>
                                            </tr>
                                            <tr>
                                                <td width="39%" class="lableText">Education</td>
                                                <td width="1%">:</td>
                                                <td width="60%">
                                                    <div class="infoText" >
                                                    {{   $item['educational_details']['education_level'] }},
                                                    {{$item['educational_details']['qualification_degree']}}     
                                                    </div>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td width="39%" class="lableText">Profession</td>
                                                <td width="1%">:</td>
                                                <td width="60%">
                                                    <div class="infoText">
                                                        {{$item['occupational_details']['occupation']}}
                                                    </div>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td width="39%" class="lableText">Jain Sect</td>
                                                <td width="1%">:</td>
                                                <td width="60%">
                                                   <div class="infoText">     
                                                    {{$item['personal_details']['sect']}}
                                                   </div>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td width="39%" class="lableText">Parent City</td>
                                                <td width="1%">:</td>
                                                <td width="60%">
                                                    <div class="infoText">
                                                        {{ $item['residential_details']['address'] }}
                                                    </div>
                                                </td>
                                            </tr>


                                            <tr>
                                                <td width="39%" class="lableText">Candidate City</td>
                                                <td width="1%">:</td>
                                                <td width="60%">
                                                   <div class="infoText" >  
                                                    {{ $item['residential_details']['address'] }}
                                                   </div>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                          
                                </tr>
         
                        </table>
                    </td>
                </tr>
             </table>
         <div class="page-break"></div>
         @endforeach
    @endif  
     
</div>



</body>
</html>