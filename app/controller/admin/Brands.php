<?php defined ('OACI') || exit ('此檔案不允許讀取。');

/**
 * @author      OA Wu <comdan66@gmail.com>
 * @copyright   Copyright (c) 2013 - 2018, OACI
 * @license     http://opensource.org/licenses/MIT  MIT License
 * @link        https://www.ioa.tw/
 */

class Brands extends AdminRestfulController {

  public function __construct () {
    parent::__construct ();
    $this->layout->with ('title', '品牌輪播')
                 ->with ('current_url', RestfulUrl::url ('admin/Brands@index'));
  }

  public function index () {

    $where = Where::create ();

    $search = Restful\Search::create ($where)
                            ->input ('標題', function ($val) { return Where::create ('title LIKE ?', '%' . $val . '%'); }, 'text');

    $total = Brand::count ($where);
    $page  = Pagination::info ($total);
    $objs  = Brand::find ('all', array (
               'order' => Restful\Order::desc ('sort'),
               'offset' => $page['offset'],
               'limit' => $page['limit'],
               'where' => $where));

    $search->setObjs ($objs)
           ->setTotal ($total)
           ->setAddUrl (RestfulUrl::add ())
           ->setSortUrl (RestfulUrl::sorts ());

    return $this->view->setPath ('admin/Brands/index.php')
                      ->with ('search', $search)
                      ->with ('pagination', implode ('', $page['links']));
  }

  public function add () {
    return $this->view->setPath ('admin/Brands/add.php');
  }

  public function create () {
    $validation = function (&$posts, &$files) {
      Validation::need ($files, 'pic', '圖片')->isUploadFile ()->formats ('jpg', 'gif', 'png')->size (1, 10 * 1024 * 1024);
      Validation::need ($posts, 'title', '標題')->isStringOrNumber ()->doTrim ()->doRemoveHtmlTags ()->length (1, 255);
      Validation::need ($posts, 'link', '鏈結')->isStringOrNumber ()->doTrim ()->doRemoveHtmlTags ()->length (1);
    };

    $transaction = function ($posts, $files, &$obj) {
      return ($obj = Brand::create ($posts))
           && $obj->putFiles ($files);
    };

    $posts = Input::post ();
    $posts['sort'] = Brand::count ();
    $files = Input::file ();

    if ($error = Validation::form ($validation, $posts, $files))
      return refresh (RestfulUrl::add (), 'flash', array ('type' => 'failure', 'msg' => '失敗！' . $error, 'params' => $posts));

    if ($error = Brand::getTransactionError ($transaction, $posts, $files, $obj))
      return refresh (RestfulUrl::add (), 'flash', array ('type' => 'failure', 'msg' => '失敗！' . $error, 'params' => $posts));

    return refresh (RestfulUrl::index (), 'flash', array ('type' => 'success', 'msg' => '成功！', 'params' => array ()));
  }

  public function edit ($obj) {
    return $this->view->setPath ('admin/Brands/edit.php');
  }

  public function update ($obj) {
    $validation = function (&$posts, &$files, &$obj) {
      $obj->pic->getValue ()
        ? Validation::maybe ($files, 'pic', '圖片')->isUploadFile ()->formats ('jpg', 'gif', 'png')->size (1, 10 * 1024 * 1024)
        : Validation::need ($files, 'pic', '圖片')->isUploadFile ()->formats ('jpg', 'gif', 'png')->size (1, 10 * 1024 * 1024);

      Validation::need ($files, 'pic', '圖片')->isUploadFile ()->formats ('jpg', 'gif', 'png')->size (1, 10 * 1024 * 1024);
      Validation::need ($posts, 'title', '標題')->isStringOrNumber ()->doTrim ()->doRemoveHtmlTags ()->length (1, 255);
      Validation::need ($posts, 'link', '鏈結')->isStringOrNumber ()->doTrim ()->doRemoveHtmlTags ()->length (1);
    };

    $transaction = function ($posts, $files, &$obj) {
      return $obj->columnsUpdate ($posts)
          && $obj->save ()
          && $obj->putFiles ($files);
    };

    $posts = Input::post ();
    $files = Input::file ();

    if ($error = Validation::form ($validation, $posts, $files, $obj))
      return refresh (RestfulUrl::edit ($obj), 'flash', array ('type' => 'failure', 'msg' => '失敗！' . $error, 'params' => $posts));

    if ($error = Brand::getTransactionError ($transaction, $posts, $files, $obj))
      return refresh (RestfulUrl::edit ($obj), 'flash', array ('type' => 'failure', 'msg' => '失敗！' . $error, 'params' => $posts));

    return refresh (RestfulUrl::index (), 'flash', array ('type' => 'success', 'msg' => '成功！', 'params' => array ()));
  }

  public function destroy ($obj) {
    if ($error = Brand::getTransactionError (function ($obj) { return $obj->destroy (); }, $obj))
      return refresh (RestfulUrl::index (), 'flash', array ('type' => 'failure', 'msg' => '失敗！' . $error, 'params' => array ()));

    return refresh (RestfulUrl::index (), 'flash', array ('type' => 'success', 'msg' => '成功！', 'params' => array ()));
  }

  public function show ($obj) {
    return $this->view->setPath ('articles/show.php');
  }

  public function status ($obj) {
    $validation = function (&$posts) {
      Validation::maybe ($posts, 'status', '狀態', Brand::STATUS_OFF)->isStringOrNumber ()->doTrim ()->doRemoveHtmlTags ()->inArray (array_keys (Brand::$statusTexts));
    };

    $transaction = function ($posts, $obj) {
      return $obj->columnsUpdate ($posts)
          && $obj->save ();
    };

    $posts = Input::post ();

    if ($error = Validation::form ($validation, $posts))
      return Output::json ($error, 400);

    if ($error = Brand::getTransactionError ($transaction, $posts, $obj))
      return Output::json ($error, 400);

    return Output::json (array (
        'status' => $obj->status
      ));
  }

  public function sorts () {
    $validation = function (&$posts) {
      Validation::maybe ($posts, 'changes', '狀態', array ())->isArray ()->doArrayValues ()->doArrayMap (function ($t) {
        if (!isset ($t['id'], $t['ori'], $t['now']))
          return Validation::error ('格式不正確(1)');

        if (!$obj = Brand::find ('one', array ('select' => 'id,sort', 'where' => Where::create ('id = ? AND sort = ?', $t['id'], $t['ori']))))
          return Validation::error ('格式不正確(2)');

        return array ('obj' => $obj, 'sort' => $t['now']);
      })->doArrayFilter ();
    };

    $posts = Input::post ();

    if ($error = Validation::form ($validation, $posts))
      return Output::json ($error, 400);

    $transaction = function ($posts) {
      foreach ($posts['changes'] as $value)
        $value['obj']->sort = $value['sort'];

      foreach ($posts['changes'] as $value)
        if (!$value['obj']->save ())
          return false;

      return true;
    };

    if ($error = Brand::getTransactionError ($transaction, $posts))
      return Output::json ($error, 400);

    return Output::json (array_map (function ($t) { return array ('id' => $t->id, 'sort' => $t->sort);}, array_column ($posts['changes'], 'obj')));
  }
}
