<?php
    require_once(dirname(__FILE__).'/../config/config.php');
    require_once(dirname(__FILE__).'/functions.php');

    try{
        //1．ログイン状態をチェック、ログインユーザの情報をセッションから取得
        session_start();
        if(!isset($_SESSION['USER'])){
            //ログインされてない場合はログイン画面へ
            redirect('/login.php');
        }
        //ログインユーザの情報をセッションから取得
        $session_user = $_SESSION['USER'];
        $pdo = connect_db();
        $err = array();
        $target_date = date('Y-m-d');
        //モーダルの自動表示判定
        $modal_view_flg = TRUE;

        if($_SERVER['REQUEST_METHOD'] == 'POST'){
            //日報踏力処理
            //入力値をPOSTパラメータから取得
            $target_date = $_POST['target_date'];
            $modal_start_time = $_POST['modal_start_time'];
            $modal_end_time = $_POST['modal_end_time'];
            $modal_break_time = $_POST['modal_break_time'];
            $modal_comment = $_POST['modal_comment'];

            //バリデーションチェック
            if(!$modal_start_time){
                $err['modal_start_time'] = '出勤時間を入力してください';
            } elseif(!check_time_format($modal_start_time)){
                $modal_start_time = '';
                $err['modal_start_time'] = '出勤時間を正しく入力してください';
            }

            if(!check_time_format($modal_end_time)){
                $modal_end_time = '';
                $err['modal_end_time'] = '退勤時間を正しく入力してください';
            }

            if(!check_time_format($modal_break_time)){
                $modal_break_time = '';
                $err['modal_break_time'] = '休憩時間を正しく入力してください';
            }

            if(mb_strlen($modal_comment, 'utf-8') > 2000){
                $err['modal_comment'] = '業務内容が長すぎます';
            }

            if(empty($err)){
                //対象日のデータがあるかどうかチェック
                $sql = "SELECT id FROM work WHERE user_id = :user_id AND date = :date LIMIT 1";
                $stmt = $pdo->prepare($sql);
                $stmt->bindValue(':user_id', (int)$session_user['id'], PDO::PARAM_INT);
                $stmt->bindValue(':date', $target_date, PDO::PARAM_STR);
                $stmt->execute();
                $work = $stmt->fetch();

                if($work){
                    //対象日のデータがあればUPDATE
                    $sql = "UPDATE work SET start_time =:start_time, end_time = :end_time, break_time = :break_time, comment = :comment WHERE id = :id";
                    $stmt = $pdo->prepare($sql);
                    $stmt->bindValue(':id', (int)$work['id'], PDO::PARAM_INT);
                    $stmt->bindValue(':start_time', $modal_start_time, PDO::PARAM_STR);
                    $stmt->bindValue(':end_time', $modal_end_time, PDO::PARAM_STR);
                    $stmt->bindValue(':break_time', $modal_break_time, PDO::PARAM_STR);
                    $stmt->bindValue(':comment', $modal_comment, PDO::PARAM_STR);
                    $stmt->execute();  
                    $work = $stmt->fetch();        
                }else{
                    //対象日のデータがなければINSERT
                    $sql = "INSERT INTO work (user_id, date, start_time, end_time, break_time, comment) VALUES(:user_id, :date, :start_time, :end_time, :break_time, :comment)";
                    $stmt = $pdo->prepare($sql);
                    $stmt->bindValue(':user_id', (int)$session_user['id'], PDO::PARAM_INT);
                    $stmt->bindValue(':date', $target_date, PDO::PARAM_STR);
                    $stmt->bindValue(':start_time', $modal_start_time, PDO::PARAM_STR);
                    $stmt->bindValue(':end_time', $modal_end_time, PDO::PARAM_STR);
                    $stmt->bindValue(':break_time', $modal_break_time, PDO::PARAM_STR);
                    $stmt->bindValue(':comment', $modal_comment, PDO::PARAM_STR);
                    $stmt->execute();  
                    $work = $stmt->fetch();  
                }
                $modal_view_flg = FALSE;
            }
        } else{
            //当日のデータがあるかどうかチェック
            $sql = "SELECT id, start_time, end_time, break_time, comment FROM work WHERE user_id = :user_id AND date = :date LIMIT 1";
            $stmt = $pdo->prepare($sql);
            $stmt->bindValue(':user_id', (int)$session_user['id'], PDO::PARAM_INT);
            $stmt->bindValue(':date', date('Y-m-d'), PDO::PARAM_STR);
            $stmt->execute();
            $today_work = $stmt->fetch();

            //モーダルの自動表示判定
            if($today_work){
                $modal_start_time = $today_work['start_time'];
                $modal_end_time = $today_work['end_time'];
                $modal_break_time = $today_work['break_time'];
                $modal_comment = $today_work['comment'];

                if(format_time($modal_start_time) && format_time($modal_end_time)){
                    $modal_view_flg = FALSE;
                }

            }else{
                $modal_start_time ='';
                $modal_end_time ='';
                $modal_break_time ='01:00';
                $modal_comment ='';
            }
        }

        //2、ユーザの業務日報データを取得
        if(isset($_GET['m'])){
            $yyyymm = $_GET['m'];
            $day_count = date('t', strtotime($yyyymm));
            if(count(explode('-', $yyyymm)) != 2){
                throw new Exception('日付の指定が不正', 500);
            }

            $check_date = new DateTime($yyyymm.'-01');
            $start_date = new DateTime('first day of -11 month 00:00');
            $end_date = new DateTime('first day of this month 00:00');

            if($check_date < $start_date || $end_date < $check_date){
                throw new Exception('日付の範囲が不正', 500);
            }

            if($check_date != $end_date){
                //表示している画面が当月ではなかったらモーダルは自動表示しない
                $modal_view_flg = FALSE;
            }
        }else{
            $yyyymm = date('Y-m');
            $day_count = date('t');
        }

        //指定年月の勤務データを取得
        $sql = "SELECT date, id, start_time, end_time, break_time, comment FROM work WHERE user_id = :user_id AND DATE_FORMAT(date, '%Y-%m') = :date";
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':user_id', (int)$session_user['id'], PDO::PARAM_INT);
        $stmt->bindValue(':date', $yyyymm, PDO::PARAM_STR);
        $stmt->execute();
        $work_list = $stmt->fetchAll(PDO::FETCH_UNIQUE);

        $page_title = '日報登録';
    }catch(Exception $e){
        redirect('/error.php');
    }
?>

<!doctype html>
<html lang="ja">

<?php include('templates/head_tag.php')?>

<body class="text-center bg-light">
    <form class="border rounded bg-white form-login">
        <h1 class="h2 my-3">月別リスト</h1>
        <select class="form-control" name="m" onchange="submit(this.form)">
            <option value="<?= date('Y-m')?>"><?= date('Y/m') ?></option>
            <?php for($i = 1; $i < 12; $i++): ?>
                <?php $target_yyyymm = strtotime("- {$i}months"); ?>
            <option value="<?= date('Y-m', $target_yyyymm) ?>" <?php if($yyyymm == date('Y-m', $target_yyyymm)) echo 'selected' ?>><?= date('Y/m', $target_yyyymm) ?></option>
            <?php endfor; ?>
        </select>
        <table class="table">
            <thead>
                <tr>
                    <th scope="col">日</th>
                    <th scope="col">出勤</th>
                    <th scope="col">退勤</th>
                    <th scope="col">休憩</th>
                    <th scope="col">業務内容</th>
                    <th scope="col"></th>
                </tr>
            </thead>
            <tbody>
                <?php for($i = 1; $i <= $day_count; $i++): ?>
                    <?php
                        $start_time ='';
                        $end_time ='';
                        $break_time ='';
                        $comment ='';

                        if(isset($work_list[date('Y-m-d', strtotime($yyyymm.'-'.$i))])){
                            $work = $work_list[date('Y-m-d', strtotime($yyyymm.'-'.$i))];

                            if($work['start_time']){
                                $start_time = date('H:i', strtotime($work['start_time']));
                            }

                            if($work['end_time']){
                                $end_time = date('H:i', strtotime($work['end_time']));
                            }

                            if($work['break_time']){
                                $break_time = date('H:i', strtotime($work['break_time']));
                            }

                            if($work['comment']){
                                $comment = mb_strimwidth($work['comment'], 0, 40, '...');
                            }
                        }
                    ?>
                <tr>
                    <th scope="row"><?= time_format_dw($yyyymm.'-'.$i) ?></th>
                    <td><?= $start_time ?></td>
                    <td><?= $end_time ?></td>
                    <td><?= $break_time ?></td>
                    <td><?= htmlspecialchars($comment, ENT_QUOTES, 'UTF-8') ?></td>
                    <td scope="col"><button type="button" class="btn btn-default" data-toggle="modal" data-target="#inputModal" data-day="<?= $yyyymm.'-'.sprintf('%02d', $i)?>" data-month="<?= date('n', strtotime($yyyymm . '-' . $i)) ?>">編集</td>
                </tr>
                <?php endfor; ?>
            </tbody> 
        </table>
    </form>

    <!-- Modal -->
    <form method="POST">
        <div class="modal fade" id="inputModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <p></p>
                        <h5 class="modal-title" id="exampleModalLabel">日報登録</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="container">
                            <div class="alert alert-primary" role="alert">
                                <span id="modal_month"><?= date('n', strtotime($target_date)) ?></span>/<span id="modal_day"><?= time_format_dw(date($target_date))?></span>
                            </div>
                        <div class="row">
                            <div class="col-sm">
                                <div class="input-group">
                                    <input type="text" class="form-control <?php if(isset($err['modal_start_time'])) echo 'is-invalid'; ?>" placeholder="出勤" id="modal_start_time" name="modal_start_time" value="<?= format_time($modal_start_time) ?>" required>
                                    <div class="input-group-prepend">
                                        <button type="button" class="input-group-text" id="start_btn">打刻</button>
                                    </div>
                                    <div class="invalid-feedback"><?= $err['modal_start_time']?></div>
                                </div>
                            </div>
                            <div class="col-sm">
                                <div class="input-group">
                                    <input type="text" class="form-control <?php if(isset($err['modal_end_time'])) echo 'is-invalid'; ?>" placeholder="退勤" id="modal_end_time" name="modal_end_time" value="<?= format_time($modal_end_time) ?>">
                                    <div class="input-group-prepend">
                                        <button type="button" class="input-group-text" id="end_btn">打刻</button>
                                    </div>
                                    <div class="invalid-feedback"><?= $err['modal_end_time']?></div>
                                </div>
                            </div>
                            <div class="col-sm">
                                <input type="text" class="form-control <?php if(isset($err['modal_break_time'])) echo 'is-invalid'; ?>" placeholder="休憩" id="modal_break_time" name ="modal_break_time" value="<?= format_time($modal_break_time) ?>">
                                <div class="invalid-feedback"><?= $err['modal_break_time']?></div>
                            </div>                   
                        </div>
                            <div class="form-group pt-3">
                                <textarea class="form-control <?php if(isset($err['modal_comment'])) echo 'is-invalid'; ?>" id="modal_comment" name="modal_comment" rows="5" placeholder="業務内容"><?= $modal_comment ?></textarea>
                                <div class="invalid-feedback"><?= $err['modal_comment']?></div>
                            </div> 
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-info rounded-pill px-5">登録</button>
                    </div>
                </div>
            </div>
        </div>
        <input type="hidden" id="target_date" name="target_date" value="<?= h($target_date) ?>">
    </form>
    
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js" integrity="sha384-DfXdz2htPH0lsSSs5nCTpuj/zy4C+OGpamoFVy38MVBnE+IbbVYUew+OrCXaRkfj" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-Fy6S3B9q64WdZWQUiU+q4/2Lc9npb8tCaSX9FK7E8HnRr0Jz8D6OP9dO5Vg3Q9ct" crossorigin="anonymous"></script>

    <script>
        <?php if($modal_view_flg): ?>
        var inputModal = new bootstrap.Modal(document.getElementById('inputModal'));
        inputModal.toggle();
        <?php endif; ?>
    
        $('#start_btn').click(function(){
            const now = new Date();
            const hour = now.getHours().toString().padStart(2, '0');
            const minute = now.getMinutes().toString().padStart(2, '0');
            $('#modal_start_time').val(hour+':'+minute);
        })
        $('#end_btn').click(function(){
            const now = new Date();
            const hour = now.getHours().toString().padStart(2, '0');
            const minute = now.getMinutes().toString().padStart(2, '0');
            $('#modal_end_time').val(hour+':'+minute);
        })

        $('#inputModal').on('show.bs.modal', function(event){
            var button = $(event.relatedTarget)
            var target_day = button.data('day')
            var target_month = button.data('month')
            $('#modal_month').text(target_month)

            //編集ボタンが押された対象日の表データを取得
            var day = button.closest('tr').children('th')[0].innerText
            var start_time = button.closest('tr').children('td')[0].innerText
            var end_time = button.closest('tr').children('td')[1].innerText
            var break_time = button.closest('tr').children('td')[2].innerText
            var comment = button.closest('tr').children('td')[3].innerText

            //取得したデータをモーダルの各欄に設定
            $('#modal_day').text(day)
            $('#modal_start_time').val(start_time)
            $('#modal_end_time').val(end_time)
            $('#modal_break_time').val(break_time)
            $('#modal_comment').val(comment)
            $('#target_date').val(target_day)

            //エラー表示をクリア
            $('#modal_start_time').removeClass('is_invalid')
            $('#modal_end_time').removeClass('is_invalid')
            $('#modal_break_time').removeClass('is_invalid')
            $('#modal_comment').removeClass('is_invalid')
        })
    </script>

</body>
</html>