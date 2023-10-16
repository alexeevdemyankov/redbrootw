<?php
include "header.php";


if ($_POST['rbp__exec'] == 1) {
    $element = redbrootw::getProduct($_POST['rbp__last']);
    $resultSet = redbrootw::setProduct($element);
}


$element = redbrootw::getProduct($_POST['rbp__last']);
$data = json_decode(get_option('redbrootw'), 1);


/**status**/
$inStock = explode(',', $data['rbo__instock']);
$outofstock = explode(',', $data['rbo__outofstock']);
$onbackorder = explode(',', $data['rbo_onbackorder']);


$status = (in_array($element['stock_status_id'], $inStock)) ? 'instock' : $element['stock_status_id'];
$status = (in_array($element['stock_status_id'], $outofstock)) ? 'outofstock' : $status;
$status = (in_array($element['stock_status_id'], $onbackorder)) ? 'onbackorder' : $status;


?>
    <div class="rbo__main">
        <div class="rbo__form">
            <form method="post">
                <p>
                    <input type="text" name="rbp__last" value="<?= $element['product_id'] ?>">
                    exec <input type="checkbox" name="rbp__exec"
                                value="1" <?= ($_POST['rbp__autocheck'] == 1) ? 'checked' : '' ?>>
                    auto <input type="checkbox" name="rbp__autocheck"
                                value="1" <?= ($_POST['rbp__autocheck'] == 1) ? 'checked' : '' ?>>
                    <input type="submit" value='Import' id="rbp__click">
                </p>

                <p><b>Name: </b><?= $element['DESCRIPTIONS']['name'] ?></p>
                <p><img width="150px" height="auto" src="<?= $data['rbo__url'] . 'image/' . $element['image'] ?>">
                </p>
                <p><b>sku: </b> <?= $element['sku'] ?></p>
                <p><b>quantity: (not working) </b> <?= $element['quantity'] ?></p>
                <p><b>stock_status_id: </b> <?= $element['stock_status_id'] ?> <?= $status ?></p>
                <p><b>Price: </b><?= $element['price'] ?></p>
                <p><b>Desc: </b><?= htmlspecialchars_decode($element['DESCRIPTIONS']['description']) ?></p>
                <p><b>tags: </b><?= $element['DESCRIPTIONS']['tag'] ?></p>
                <p><b>Params: </b></p>
                <ul>
                    <?php foreach ($element['PROPERTIES'] as $property) { ?>
                        <li style="display: inline-block"><b><?= $property['name'] ?></b>: <?= $property['text'] ?>
                            |
                        </li>
                    <?php } ?>
                </ul>

                <p><b>Category: </b></p>
                <ul>
                    <?php foreach ($element['CATEGORY'] as $cats) { ?>
                        <?php foreach ($cats as $cat) { ?>
                            <li style="display: inline-block"><?= $cat['DESCRIPTION']['name'] ?>
                                |
                            </li>
                        <?php } ?>
                    <?php } ?>
                </ul>

                <?php if (sizeof($element['RELATED']) > 0) { ?>


                    <p><b>Variations:</b></p>
                    <?php foreach ($element['RELATED'] as $related) { ?>

                        <?php
                        $status = (in_array($related['stock_status_id'], $inStock)) ? 'instock' : $related['stock_status_id'];
                        $status = (in_array($related['stock_status_id'], $outofstock)) ? 'outofstock' : $status;
                        $status = (in_array($related['stock_status_id'], $onbackorder)) ? 'onbackorder' : $status;
                        ?>

                        <p><b>Name: </b><?= $related['DESCRIPTIONS']['name'] ?></p>
                        <p><b>sku: </b> <?= $related['sku'] ?></p>
                        <p><img width="150px" height="auto"
                                src="<?= $data['rbo__url'] . 'image/' . $related['image'] ?>"></p>
                        <p><b>Price: </b><?= ($related['price'] > 0) ? $related['price'] : $element['price'] ?></p>

                        <p><b>quantity: </b> <?= $related['quantity'] ?></p>
                        <p><b>stock_status_id: </b> <?= $related['stock_status_id'] ?> <?= $status ?></p>

                        <p><b>Params Variant: </b></p>
                        <ul>
                            <?php foreach ($related['OPTIONS'] as $option) { ?>
                                <li style="display: inline-block">
                                    <b><?= $option['name'] ?></b>: <?= $option['namevalue'] ?>
                                    |
                                </li>
                            <?php } ?>
                        </ul>
                        <hr>
                    <?php } ?>
                <?php } ?>
                <p><b>SEO: </b></p>
                <p><b>Title: </b><?= $related['DESCRIPTIONS']['meta_title'] ?></p>
                <p><b>Description: </b><?= $related['DESCRIPTIONS']['meta_description'] ?></p>
                <p><b>Keyword: </b><?= $related['DESCRIPTIONS']['meta_keyword'] ?></p>

            </form>
        </div>
    </div>


<?php
if ($resultSet == 1 && $_POST['rbp__autocheck'] == 1) {
    ?>
    <script>
        document.getElementById('rbp__click').click();
    </script>
<?php } ?>