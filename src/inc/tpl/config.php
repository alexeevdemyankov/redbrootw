<?php
include "header.php";


if ($_POST) update_option('redbrootw', json_encode($_POST));
$data = json_decode(get_option('redbrootw'), 1);

?>
<div class="rbo__main">
    <div class="rbo__form">
        <form method="post">
            <div>
                <p>Language ID
                    <input type="text" name="rbo__lid" value="<?= $data['rbo__lid'] ?>">
                </p>
            </div>

            <div>
                <p>In stock ids (,)
                    <input type="text" name="rbo__instock" value="<?= $data['rbo__instock'] ?>">
                </p>
            </div>

            <div>
                <p>In outofstock ids (,)
                    <input type="text" name="rbo__outofstock" value="<?= $data['rbo__outofstock'] ?>">
                </p>
            </div>

            <div>
                <p>In onbackorder ids (,)
                    <input type="text" name="rbo_onbackorder" value="<?= $data['rbo_onbackorder'] ?>">
                </p>
            </div>

            <div>
                <p>URL Site (https://sitename.com/)
                    <input type="text" name="rbo__url" value="<?= $data['rbo__url'] ?>">
                </p>
            </div>

            <div>
                <p>Mysql host
                    <input type="text" name="rbo__sqlhost" value="<?= $data['rbo__sqlhost'] ?>">
                </p>
            </div>
            <div>
                <p>Mysql port
                    <input type="text" name="rbo__sqlport" value="<?= $data['rbo__sqlport'] ?>">
                </p>
            </div>
            <div>
                <p>Mysql login
                    <input type="text" name="rbo__sqllogin" value="<?= $data['rbo__sqllogin'] ?>">
                </p>
            </div>
            <div>
                <p>Mysql password
                    <input type="password" name="rbo__sqlpassword" value="<?= $data['rbo__sqlpassword'] ?>">
                </p>
            </div>
            <div>
                <p>Mysql DB
                    <input type="text" name="rbo__sqldb" value="<?= $data['rbo__sqldb'] ?>">
                </p>
            </div>
            <div>
                <input type="submit" value="SAVE CONFIG">
            </div>
        </form>
    </div>
</div>


