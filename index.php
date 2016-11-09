<?php
    session_start();
    if(isset($_SESSION['money']) && $_SESSION['money'] != ''){}
    else
    {
        $_SESSION['money'] = 10000.00;

    }

    if(isset($_SESSION['stocks']) && !empty($_SESSION['stocks'])){}
    else
    {
        $_SESSION['stocks'] = [];
    }

    if(isset($_POST['action']) && $_POST['action'] == 'ajax')
    {
        $data = file_get_contents("http://data.benzinga.com/rest/richquoteDelayed?symbols=".$_POST['symbol']);
        $dataArr = json_decode($data);
        if(isset($dataArr->$_POST['symbol']) && !empty($dataArr->$_POST['symbol']))
        {
            $stockDataArr = (array) $dataArr->$_POST['symbol'];
            $html = '<h4>'.$stockDataArr['name'].'</h4>
                      <table class="table">
                          <thead>
                              <tr>
                                  <th>Bid</th>
                                  <th>Ask</th>
                              </tr>
                          </thead>
                          <tbody>
                              <tr>
                                  <td>'.$stockDataArr['bidPrice'].'</td>
                                  <td>'.$stockDataArr['askPrice'].'</td>
                              </tr>
                              <tr>
                                  <td><input type="number" style="width:100px;" class="form-control" name="quantity" id="quantity" placeholder="Quantity"/></td>
                                  <td>
                                      <input type="hidden" id="symbolsearched" value="'.$_POST['symbol'].'"/>
                                      <input type="hidden" id="bidPrice" value="'.$stockDataArr['bidPrice'].'"/>
                                      <input type="hidden" id="askPrice" value="'.$stockDataArr['askPrice'].'"/>
                                      <button type="button" class="btn btn-primary" onclick="transaction(\'buy\');">Buy</button>
                                      <button type="button" class="btn btn-primary" onclick="transaction(\'sell\');">Sell</button>
                                  </td>
                              </tr>
                          </tbody>
                      </table>';

            echo $html;exit;
        }
        else
        {
            echo 0;exit;
        }
    }
    else if(isset($_POST['action']) && $_POST['action'] == 'buy')
    {
        if(($_POST['askPrice']*$_POST['quantity']) > $_SESSION['money'])
        {
            echo "Not enough cash available to buy selected number of stocks";exit;
        }
        else if(empty($_SESSION['stocks']))
        {
            $stock = [];
            $stock['symbol'] = $_POST['symbolsearched'];
            $stock['quantity'] = $_POST['quantity'];
            $stock['paid'] = $_POST['askPrice']*$_POST['quantity'];
            $_SESSION['money'] = $_SESSION['money'] - $stock['paid'];
            $_SESSION['stocks'][] = $stock;
        }
        else
        {
            $index = searchForSymobol($_POST['symbolsearched'], $_SESSION['stocks']);
            if($index == '-1')
            {
                $stock = [];
                $stock['symbol'] = $_POST['symbolsearched'];
                $stock['quantity'] = $_POST['quantity'];
                $stock['paid'] = $_POST['askPrice'] * $_POST['quantity'];
                $_SESSION['money'] = $_SESSION['money'] - $stock['paid'];
                $_SESSION['stocks'][] = $stock;
            }
            else
            {
                $existStock = $_SESSION['stocks'][$index];
                $existStock['quantity'] = $existStock['quantity'] + $_POST['quantity'];
                $existStock['paid'] = $existStock['paid'] + ($_POST['askPrice'] * $_POST['quantity']);
                $_SESSION['money'] = $_SESSION['money'] - ($_POST['askPrice'] * $_POST['quantity']);
                $_SESSION['stocks'][$index] = $existStock;
            }
        }

        echo 1;exit;
    }
    else if(isset($_POST['action']) && $_POST['action'] == 'sell')
    {
        if(empty($_SESSION['stocks']))
        {
            echo "No stocks in portfolio";exit;
        }
        else
        {
            $index = searchForSymobol($_POST['symbolsearched'], $_SESSION['stocks']);
            if($index == '-1')
            {
                echo "No stock available";exit;
            }
            else
            {
                $existStock = $_SESSION['stocks'][$index];
                if($existStock['quantity'] < $_POST['quantity'])
                {
                    echo $existStock['quantity'] . " stock available only";exit;
                }
                else
                {
                    $existStock['quantity'] = $existStock['quantity'] - $_POST['quantity'];
                    $existStock['paid'] = $existStock['paid'] - ($_POST['bidPrice'] * $_POST['quantity']);
                    $_SESSION['money'] = $_SESSION['money'] + ($_POST['bidPrice'] * $_POST['quantity']);
                    if($existStock['quantity'] == 0)
                    {
                        unset($_SESSION['stocks'][$index]);
                    }
                    else
                    {
                        $_SESSION['stocks'][$index] = $existStock;
                    }

                    echo 1;exit;
                }
            }
        }
    }

    function searchForSymobol($symbol, $array) {
       foreach ($array as $key => $val) {
           if ($val['symbol'] === $symbol) {
               return $key;
           }
       }
       return -1;
    }
?>
<html>
    <head>
        <title>Stock Exchange</title>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">
        <link rel="stylesheet" href="css/jquery-ui.css">
        <link rel="stylesheet" href="css/bootstrap.min.css">
        <style>
            .error{
                border-color: red;
            }

        </style>
    </head>
    <body style="font-family: calibri;">
        <div class="container-fluid">
            <table class="table">
                <thead>
                    <tr>
                        <th>Stock Exchange</th>
                        <th><input type="text" class="form-control" name="symbol" id="symbol" placeholder="Enter Symbol"/></th>
                        <th><button type="button" class="btn btn-primary" onclick="getSymbolDetail();">Lookup</button></th>
                    </tr>
                </thead>
                <tbody>
                    <tr id="stock_detail_row" style="display: none;">
                        <td colspan="3" id="stock_detail" style="border: 2px solid black;">
                        </td>
                    </tr>
                    <tr id="current_portfolio">
                        <td colspan="3">
                            <h4>Current Portfolio   <b class="pull-right"><?php echo "Cash : $".$_SESSION['money'];?></b></h4>
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Company</th>
                                        <th>Quantity</th>
                                        <th>Price Paid</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if(!empty($_SESSION['stocks'])){ ?>
                                        <?php foreach($_SESSION['stocks'] as $stock){
                                            $data = file_get_contents("http://data.benzinga.com/rest/richquoteDelayed?symbols=".$stock['symbol']);
                                            $dataArr = json_decode($data);
                                            $stockDataArr = (array) $dataArr->$stock['symbol'];
                                        ?>
                                            <tr>
                                                <td><?php echo $stockDataArr['name']; ?></td>
                                                <td><?php echo $stock['quantity']; ?></td>
                                                <td><?php echo $stock['paid']; ?></td>
                                                <td><button type="button" class="btn btn-primary" onclick="getSymbolDetail('<?php echo $stock['symbol'];?>');">View Stock</button></td>
                                            </tr>
                                        <?php } ?>
                                    <?php }else{ ?>
                                        <tr>
                                            <td colspan="3">No Stocks Found</td>
                                        </tr>
                                    <?php } ?>
                                </tbody>
                            </table>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <script src="js/jquery.min.js"></script>
        <script src="js/jquery-ui.js"></script>
        <script src="js/bootstrap.min.js"></script>
        <script>
            function getSymbolDetail(existSymbol)
            {
                if (typeof existSymbol == 'undefined')
                {
                    var symbol = $("#symbol").val();
                    var sell = false;
                }
                else
                {
                    var symbol = existSymbol;
                    var sell = true;
                }

                if(symbol == '')
                {
                    $("#symbol").addClass("error");
                }
                else
                {
                    $("#symbol").removeClass("error");
                    $.ajax({
                        url: "index.php",
                        type: 'post',
                        data: {
                            "action": 'ajax',
                            "symbol": symbol,
                            "sell": sell
                        },
                        success: function(response) {
                            if(response == 0)
                            {
                                alert("Incorrect Symbol");
                            }
                            else
                            {
                                $("#stock_detail").html(response);
                                $("#stock_detail_row").show();
                            }
                        }
                    });
                }
            }

            function transaction(action)
            {
                var quantity = $("#quantity").val();
                var symbolsearched = $("#symbolsearched").val();
                var bidPrice = $("#bidPrice").val();
                var askPrice = $("#askPrice").val();
                if(quantity != '' && quantity > 0)
                {
                    $("#quantity").removeClass("error");
                    $.ajax({
                        url: "index.php",
                        type: 'post',
                        data: {
                            "action": action,
                            "quantity": quantity,
                            "symbolsearched": symbolsearched,
                            "bidPrice": bidPrice,
                            "askPrice": askPrice
                        },
                        success: function(response) {
                            if(response != '1')
                            {
                                alert(response);
                            }
                            else
                            {
                                location.reload();
                            }
                        }
                    });
                }
                else
                {
                    $("#quantity").addClass("error");
                }
            }
        </script>
    </body>
</html>