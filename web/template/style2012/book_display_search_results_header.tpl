<p>{NUM_RESULTS_FOUND}: {NB_RESULTS}</p>
<div class="record-topbar">
  <div class="record-topbar__actions">
    <form method="GET" action="{BOOKS_MODIFY_SEARCH_FORM}" class="record-topbar__back-form">
      <input type="hidden" name="m" value="bo">
      <input type="hidden" name="a" value="vi">
      <input type="submit" class="submit submit--secondary" value="{BOOKS_MODIFY_SEARCH_SUBMIT}">
    </form>
  </div>
  <div class="record-topbar__nav">{NAV}</div>
  <div class="record-topbar__tools">
    <form method="GET" action="index.php" class="record-topbar__export-form" data-no-page-loader="1">
      <input type="hidden" name="m" value="bo">
      <input type="hidden" name="a" value="vi">
      <input type="hidden" name="sa" value="re">
      <input type="hidden" name="research" value="ok">
      <input type="hidden" name="book_action" value="export_excel">
      <button type="submit" class="submit submit--secondary">{BOOKS_EXPORT_EXCEL}</button>
    </form>
  </div>
</div>
<form method='post' action='{SELECTION_URL}' name='selection'>
<table class="bordered">
<tr>
    <th>&nbsp;</th>
    <th>&nbsp;</th>
    <th>&nbsp;</th>
    <th>&nbsp;</th>
    <th>{BOOKS_AUTHOR}</th>
    <th>{BOOKS_TITLE}</th>
    <th>{BOOKS_DESCRIPTION}</th>
    <th>{BOOKS_PRICE}</th>
    <th>{BOOKS_QUANTITY}</th>
    <th>{BOOKS_LOCATION}</th>
</tr>
