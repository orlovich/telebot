Ext.define('Application.components.telebotWindow', {
  extend: 'Ext.Window',
  title: 'Добавление бота в Telegram',
  modal: true,
  width: 400,
  height: 350,
  border: false,
  bodyBorder: false,
  buttonAlign: 'center',
  initComponent: function () {
    var component = this;

    Ext.apply(this, {
      items: component._getItems(),
      buttons: component._getButtons(),
      listeners: {
        beforerender: function () {
          var displayParams = {mask:true, wait_text:__("Загрузка...")};

          performRPCCall(RPC_telebot.Ui.addBot, [], displayParams, function(response) {
            if (response.success) {
              component.instructions.update({href: response.hRef});
              component.qrCode.update({qrCodeSrc: response.qrCode});
            } else {
              component.close();
              echoResponseMessage(response);
            }
          });
        }
      }
    });

    Application.components.telebotWindow.superclass.initComponent.call(this);
  },
  /**
   * @returns {array}
   * @private
   */
  _getItems: function () {
    return [
      {
        xtype: 'form',
        width: 'auto',
        border: false,
        bodyBorder: false,
        buttonAlign: 'center',
        padding: 7,
        bodyStyle: {"background-color": "#dfe8f6"},
        items: [
          {
            xtype: 'container',
            style: 'padding-bottom:10px;font-size:12px',
            border: false,
            items: [
              {
                xtype: 'box',
                ref: '../../instructions',
                tpl: this.getTpl()
              },
              {
                xtype: 'box',
                ref: '../../qrCode',
                tpl: new Ext.Template("<img src='{qrCodeSrc}'>"),
                style: 'margin-top: 35px; text-align: center;'
              }
            ]
          }
        ]
      }
    ];
  },
  /**
   * @returns {array}
   * @private
   */
  _getButtons: function () {
    var self = this;

    return [{
      xtype: 'button',
      text: __(["OK", "buttons"]),
      handler: function () {
        self.close();
      }
    }];
  },
  /**
   * @returns {Ext.Template}
   */
  getTpl: function () {
    return new Ext.Template(
      "<div>" +
        "3 варианта добавления бота с применением вашего смартфона:" +
        "<ul class='telegram_instructions_items'>" +
          "<li>отсканировать <b>qr-код</b> ИЛИ</li>" +
          "<li>набрать ссылку <a href='{href}' target='_blank'>t.me/" + getConfigValue('telebot->username') + "</a> ИЛИ</li>" +
          "<li>найти в Telegram <b>контакт</b> с именем «" + getConfigValue('telebot->name') + "»</li>" +
        "</ul>" +
      "</div>"
    );
  }
});