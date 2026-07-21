class SheetsPilot_Notification {
  
  constructor() {
    
    // classes
    this.g_classActive = "ue-active";
    this.g_notificationClass = "unlimitedai-plugin__notification";
    this.g_notificationIconClass = "unlimitedai-plugin__notification-inner__icon";
    this.g_notificationTextClass = "unlimitedai-plugin__notification-inner__text";
    
    // selectors
    this.g_notificationSelector = `.${this.g_notificationClass}`;
    this.g_notificationIconSelector = `.${this.g_notificationIconClass}`;
    this.g_notificationTextSelector = `.${this.g_notificationTextClass}`;
    this.g_copyTextSelector = ".copy_text";
    
    // objects
    this.g_objPostsEditor = jQuery("#unlimitedai-plugin");
    this.g_objNotification = this.g_objPostsEditor.find(this.g_notificationSelector);
    this.g_objNotificationIcon = this.g_objPostsEditor.find(this.g_notificationIconSelector);
    this.g_objNotificationText = this.g_objPostsEditor.find(this.g_notificationTextSelector);
    
  }
  
  initEvents() {
    var self = this;
    
    this.g_objPostsEditor.on("click", self.g_copyTextSelector, (e) => {
      this.showNotification(e);
    });
    
  }
  
  showNotification(e) {
    var self = this;
    this.g_objNotification.addClass(this.g_classActive);

    setTimeout(() => {
      self.hideNotification();
    }, 2000);
  }

  showErrorMessage(html) {
    if (!this.g_objNotification || !this.g_objNotification.length) {
      return false;
    }

    this.g_objNotification.addClass('unlimitedai-plugin__notification--error');
    if (this.g_objNotificationText && this.g_objNotificationText.length) {
      this.g_objNotificationText.html(html);
    }

    var self = this;
    this.g_objNotification.addClass(this.g_classActive);

    if (this.g_errorHideTimer) {
      clearTimeout(this.g_errorHideTimer);
    }

    this.g_errorHideTimer = setTimeout(function () {
      self.hideNotification();
      self.g_objNotification.removeClass('unlimitedai-plugin__notification--error');
    }, 6000);

    return true;
  }
  
  hideNotification() {
    
    this.g_objNotification.removeClass(this.g_classActive);
  }  

  insertText(text){

    this.g_objNotificationText.text(text);
  }
  
}