<div class="rbo__header">
    <div class="rbo__logo">
        <img src="<?= plugins_url('/redbrootw/img/logo.png') ?>">
    </div>
    <div class="rbo__menu">
        <ul>
            <li>
                <a href="?page=redbrootw_admin">About</a>
            </li>
            <li>
                <a href="?page=redbrootw_admin&plg_action=config">Config</a>
            </li>
            <li>
                <a href="?page=redbrootw_admin&plg_action=update">Update</a>
            </li>
        </ul>
    </div>
</div>

<style>
    .rbo__header {

        display: block;
        overflow: hidden;
        background-color: darkred;
        padding: 0px;
        margin: 0px;
        margin-top: 20px;
        margin-right: 20px;

    }

    .rbo__header .rbo__logo {
        line-height: 50px;

    }

    .rbo__header .rbo__logo,
    .rbo__header .rbo__menu {
        display: block;
        float: left;
    }


    .rbo__header .rbo__logo img {
        height: 45px;
        display: block;
        padding: 5px;
        width: auto;
    }

    .rbo__header .rbo__menu ul {
        margin: 0px;
    }

    .rbo__header .rbo__menu li {
        display: inline-block;
        padding: 0px;
        margin: 0px;
    }


    .rbo__header .rbo__menu li a {
        display: block;
        border: 1px solid lightgray;
        line-height: 46px;
        margin-top: 2px;
        margin-bottom: 2px;
        text-align: center;
        vertical-align: middle;
        padding-left: 10px;
        padding-right: 10px;
        color: white;
        text-decoration: none;
        text-transform: uppercase;
        width: 120px;
    }

    .rbo__header .rbo__menu li a:hover {
        box-shadow: inset 0 0 0 2em white;
        color: darkred;
    }

    .rbo__main {
        border: 1px solid darkgray;
        padding: 10px;
        margin-right: 20px;
        background-color: white;
    }

    .rbo__form textarea {
        min-height: 150px;
    }

    .rbo__form textarea,
    .rbo__form input[type="text"],
    .rbo__form input[type="password"] {
        width: 100%;
    }

    .rbo__main {
        display: flex;
        flex-direction: row;
    }

    .rbo__main .rbo__form {
        flex-grow: 1;
        display: flex;
        flex-direction: column;

    }

    .rbo__main .rbo__list {
        display: flex;
        flex-direction: column;
        min-width: 150px;
        padding: 15px;
    }


    .rbo__main .rbo__list div {
        margin-bottom: 0.5rem;
    }

    .rbo__main .rbo__list a {

        width: 100%;
        display: block;
        margin-bottom: 0.5rem;

    }



</style>