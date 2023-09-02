<?php
require_once(dirname(__FILE__).'/../../config/config.php');
require_once(dirname(__FILE__).'/../functions.php');
try{
    session_start();

    if(isset($_SESSION['USER']) && $_SESSION['USER']['auth_type'] == 1){
        //ログイン済みの場合はHOMEへ
        redirect('/admin/user_list.php');
    }

    if($_SERVER['REQUEST_METHOD'] == 'POST'){
        //POST処理時

        check_token();

        //１，入力値を取得
        $user_no = $_POST['user_no'];
        $password = $_POST['password'];

    //echo $user_no.'<br>';
    //echo $password;
    //exit;

        //２，バリデーションチェック
        $err = array();

        if(!$user_no){
            $err['user_no'] = '社員番号を入力してください';
        } elseif(!preg_match('/^[0-9]+$/', $user_no)){
            $err['user_no'] = '社員番号を正しく入力してください';
        } elseif(mb_strlen($user_no, 'utf-8') > 20){
            $err['user_no'] = '社員番号が長すぎます';
        }

        if(!$password){
            $err['password'] = 'パスワードを入力してください';
        }

        if(empty($err)){
            //３，データベースに照会
            $param = 'mysql:dbname='.DB_NAME.';host='.DB_HOST;
            $pdo = new PDO($param, DB_USER, DB_PASSWORD);
            $pdo->query('SET NAMES utf8;');

            $sql = "SELECT * FROM user WHERE user_no = :user_no  AND auth_type = 1 LIMIT 1";
            $stmt = $pdo -> prepare($sql);
            $stmt->bindValue(':user_no', $user_no, PDO::PARAM_STR);
            $stmt->execute();
            $user = $stmt->fetch();

            if($user && password_verify($password, $user['password'])){
                //４，ログイン処理
                $_SESSION['USER'] = $user;

                //５，HOME画面へ移動
                redirect('/admin/user_list.php');
            }else{
                $err['password'] = '認証に失敗しました';
            }
        }
    }else{
        //初回アクセス時
        $user_no = "";
        $password = "";

        set_token();
    }
    $page_title = 'ログイン';
}catch(Exception $e){
        redirect('/error.php');
    }

?>

<!doctype html>
<html lang="ja">
<?php include('templates/head_tag.php')?>
<body class="text-center bg-info">

    <form class="border rounded bg-white form-login" method="post">
        <h1 class="h2 my-3">Login</h1>
        <div class="form-group pt-3">
            <input type="text" class="form-control rounded-pill <?php if(isset($err['user_no'])) echo 'is-invalid'; ?>" name="user_no" value="<?= $user_no ?>" placeholder="社員番号" required>
            <div class="invalid-feedback"><?= $err['user_no']?></div>
        </div>
        <div class="form-group">
            <input type="password" class="form-control rounded-pill <?php if(isset($err['password'])) echo 'is-invalid'; ?>" name="password" placeholder="パスワード">
            <div class="invalid-feedback"><?= $err['password']?></div>
        </div>
        <button type="submit" class="btn btn-info text-black rounded-pill px-5 my-4">ログイン</button>
        <input type="hidden" name="CSRF_TOKEN" value="<?= $_SESSION['CSRF_TOKEN'] ?>">
    </form>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js" integrity="sha384-DfXdz2htPH0lsSSs5nCTpuj/zy4C+OGpamoFVy38MVBnE+IbbVYUew+OrCXaRkfj" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-Fy6S3B9q64WdZWQUiU+q4/2Lc9npb8tCaSX9FK7E8HnRr0Jz8D6OP9dO5Vg3Q9ct" crossorigin="anonymous"></script>
</body>
</html>
