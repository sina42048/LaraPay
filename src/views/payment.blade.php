<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>پرداخت</title>
</head>
<style>
    body{
        overflow: hidden;
        position: absolute;
        width: 100%;
        height: 100%;
        display: flex;
        justify-content: center; /*centers items on the line (the x-axis by default)*/
        align-items: center; /*centers items on the cross-axis (y by default)*/
    }
    .container {
        width: 400px;
        height: 200px;
        border: 1px solid black;
        background-color: bisque;
        border-radius: 50px;
        display: flex;
        justify-content: center;
        align-items: center;
        flex-direction: column;
    }
</style>
<body>
    <div class="container">
        <p>در حال انتقال به صفحه پرداخت</p>
        <p id="count">5</p>
        <form id="payment" action="<?php echo $url ?>" method="<?php echo $method ?>">
            @foreach ($inputs as $key => $value)
                <input type="hidden" name="<?php echo $key ?>" value="<?php echo $value ?>">
            @endforeach
        </form>
    </div>
</body>
<script>
    let timer;

    function counter() {
        $counterEl = document.getElementById('count');
        if (+$counterEl.innerHTML <= 0) {
            $formEL = document.getElementById('payment');
            $formEL.submit();
            return clearTimeout(timer);
        }
        timer = setTimeout('counter()', 1000);
        +$counterEl.innerHTML--;
    }

    counter();
</script>
</html>