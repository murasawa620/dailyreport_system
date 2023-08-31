<?php
require_once(dirname(__FILE__).'/../../config/config.php');
require_once(dirname(__FILE__).'/../functions.php');
require_once(dirname(__FILE__).'/../../lib/crypt.php');

try{
    session_start();

    if(!isset($_SESSION['USER']) || $_SESSION['USER']['auth_type'] != 1){
        //ログインされてないの場合はログインへ
        redirect('/admin/login.php');
    }

    $pdo = connect_db();

    $sql = "SELECT * FROM user";
    $stmt = $pdo->query($sql);
    $user_list = $stmt->fetchAll();
    $page_title = '社員一覧'
}catch(Exception $e){
    redirect('/error.php');
}
?>

<!doctype html>
<html lang="ja">
<?php include('templates/head_tag.php')?>
<body class="text-center bg-info">

    <form class="border rounded bg-white form-user-list" action="index.php">
        <h1 class="h2 my-3">社員一覧</h1>

        <table class="table">
            <thead>
                <tr>
                    <th scope="col">社員番号</th>
                    <th scope="col">社員名</th>
                    <th scope="col">権限</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($user_list as $user):?>
                    <tr>
                        <th scope="row"><?= $user['user_no'] ?></th>
                        <td><a href="/admin/user_result.php?id=<?= $user['id'] ?>"><?= decrypt($user['name'], $secret_key, $secret_iv) ?></a></td>
                        <th scope="row"><?php if($user['auth_type']==1)echo '管理者' ?></th>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </form>
    
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js" integrity="sha384-DfXdz2htPH0lsSSs5nCTpuj/zy4C+OGpamoFVy38MVBnE+IbbVYUew+OrCXaRkfj" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-Fy6S3B9q64WdZWQUiU+q4/2Lc9npb8tCaSX9FK7E8HnRr0Jz8D6OP9dO5Vg3Q9ct" crossorigin="anonymous"></script>
</body>
</html>