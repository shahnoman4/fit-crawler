<html>
    <head>
    <script src="https://code.jquery.com/jquery-3.4.1.min.js"
        integrity="sha256-CSXorXvZcTkaix6Yvo6HppcZGetbYMGWSFlBw8HfCJo="
        crossorigin="anonymous"></script>
    </head>
    <button>Start</button><br>
    <body>
        <script>

            var count = 0;
            $("button").click(function(){
                start_Crawler();
            });
            function start_Crawler(){
                count++;
                $.ajax({
                    url: "fetch_new_to_crawl.php", 
                    success: function(res){
                        console.log(json = JSON.parse(res))

                        $.each(json, function(i, item) {
                            var xhr = $.ajax({
                                url: "crawl_backend.php",
                                type: "POST",
                                data:{find_url:item.source_url,id:item.id}, 
                                success: function(response){
                                    console.log(response.data)
                                }}
                            );
                            setTimeout(function(){
                                xhr.abort();
                            }, 10000);
                        });
                        if(count <= 50){
                            start_Crawler();
                        }

                    }
                });
            }
                
            
        </script>
    </body>
</html>