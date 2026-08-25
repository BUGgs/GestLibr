<div class="record-topbar">
  <div class="record-topbar__actions">
    <form method="GET" action="{BOOKS_MODIFY_SEARCH_FORM}" class="record-topbar__back-form">
      <input type="hidden" name="m" value="bo">
      <input type="hidden" name="a" value="vi">
      <input type="submit" class="submit submit--secondary" value="{BOOKS_MODIFY_SEARCH_SUBMIT}">
    </form>
    <!-- BEGIN showReturnButton -->
    <form method="POST" action="{BOOKS_SEARCH_FORM}" class="record-topbar__back-form">
      <input type="hidden" name="sub" value="ok">
      <input type="hidden" name="research" value="ok">
      <input type="submit" class="submit" value="{BOOKS_SEARCH_CACHE_SUBMIT}">
    </form>
    <!-- END showReturnButton -->
  </div>
  <div class="record-topbar__nav">{NAV}</div>
</div>
<!-- BEGIN isbnImportReturn -->
<form action="?m=bo&a=ci" method="post">
  <input type="submit" value="{CREATEFROMISBN}" class="submit" />
</form>                    

<!-- END isbnImportReturn -->
