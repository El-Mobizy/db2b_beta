

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    
    <style>
        #div1{
            background-color: white;
            padding: 3%;
        }
        body{
            margin : 10%
        }


        #div2{
            background-color: aliceblue;
            padding: 5%;
            margin-left: 10%;
            margin-right: 10%;
            height: auto;
            width: auto;
        }
        .container{
            height: 150px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .a{
            width: 10em;
        }
      
    </style>
</head>
<body>
    <div id="div1"> 
        
        <div style="width: 100%; height:3cm;background-color : white">
            <div class="container" >
                <div class="a"></div>
                <div class="a">    <img src="{{$message->embed(public_path('image/DB2B1.jpg')) }}"  style="width: 2cm; height:2cm"/>
                </div>
                <div class="a"></div>
            </div>
          
        </div>
        <br>

        <div id="div2">
            <p style="padding: 5%;margin: 5%;">

                <h3 style="text-align: center;">{{ $mail ['title'] }}</h3>

                <p style="text-align: center;">  {{$mail ['body'] }}</p>

            </p>
        </div>

        <div  class="" style="text-align: center; padding-top:5%;padding-left:50%">
            <div class="a"></div>
            <div class="a"  style="text-align: center;">
                <div class="footer">
        <p>
                    Sent with ❤️  from db2b-shop
                </p>
        </div>
            </div>
            <div class="a"></div>
        </div>
      


    </div>
</body>
</html>




