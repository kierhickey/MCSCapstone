var ModalType = {
    Ok: 1,
    YesNo: 2,
    OkCancel: 3,
    YesNoCancel: 4
}

var ButtonType = {
    Ok: 1,
    Yes: 2,
    No: 3,
    Cancel: 4
}

var Modal = function (config) {
    if (!config.message) throw "NoMessageSuppliedException";
    if (!config.type) throw "NoModalTypeSuppliedException";

    var visible = false;

    if ($("head").html().indexOf("\"webroot/css/modal.css\"") === -1) {
        $("head").append(
            $("<link/>", {
                type: "text/css",
                rel: "stylesheet",
                href: "webroot/css/modal.css"
            })
        )
    }

    var modalObj;

    var defaultButtonHandler = function (event) {modalObj.close()};

    var createButton = function(buttonType, config) {
        switch (buttonType) {
            case ButtonType.Ok:
                return $("<button></button>", {
                    class: "modal-button ok",
                    text: "Ok",
                    on: {
                        click: config.ok ? function (event) {config.ok(modalObj);} : defaultButtonHandler
                    }
                });
            case ButtonType.Cancel:
                return $("<button></button>", {
                    class: "modal-button cancel",
                    text: "Cancel",
                    on: {
                        click: config.cancel ? function (event) {config.cancel(modalObj);} : defaultButtonHandler
                    }
                });
            case ButtonType.Yes:
                return $("<button></button>", {
                    class: "modal-button yes",
                    text: "Yes",
                    on: {
                        click: config.yes ? function (event) {config.yes(modalObj);} : defaultButtonHandler
                    }
                });
            case ButtonType.No:
                return $("<button></button>", {
                    class: "modal-button no",
                    text: "No",
                    on: {
                        click: config.no ? function (event) {config.no(modalObj);} : defaultButtonHandler
                    }
                });
            default:
                throw "ArgumentException";
        }
    };

    var getButtons = function (modalType, config) {
        var buttons = [];

        switch (modalType) {
            case ModalType.Ok:
                buttons.push(createButton(ButtonType.Ok, config));
                break;
            case ModalType.OkCancel:
                buttons.push(createButton(ButtonType.Cancel, config));
                buttons.push(createButton(ButtonType.Ok, config));
                break;
            case ModalType.YesNoCancel:
                buttons.push(createButton(ButtonType.Yes, config));
                buttons.push(createButton(ButtonType.No, config));
                buttons.push(createButton(ButtonType.Cancel, config));
                break;
            case ModalType.YesNo:
                buttons.push(createButton(ButtonType.Yes, config));
                buttons.push(createButton(ButtonType.No, config));
                break;
            default:
                throw "ArgumentException";
        }

        return buttons;
    };

    modalObj = {
        title: config.title || "Message",
        message: config.message,
        type: config.type,
        isVisible: function () {return visible;},
        close: function () {
            if (!visible) return;

            $(".modal-shadow").remove();

            visible = false;
        },
        show: function () {
            if (visible) return;

            var me = this;
            var buttons = getButtons(me.type, config);

            var modal = $("<div></div>", {
                class: "modal-shadow",
                html: [
                    $("<div></div>", {
                        class: "modal-shadow-inner",
                        html: [
                            $("<div></div>", {
                                class: "modal-container",
                                html: [
                                    $("<div></div>", {
                                        class: "modal-header",
                                        html: [
                                            $("<div></div>", {
                                                class: "modal-header-text",
                                                text: me.title
                                            })
                                        ]
                                    }),
                                    $("<div></div>", {
                                        class: "modal-body",
                                        html: [
                                            $("<span></span>", {
                                                class: "modal-body-text",
                                                html: me.message
                                            }),
                                            $("<div></div>", {
                                                class: "modal-button-dock",
                                                html: buttons
                                            })
                                        ]
                                    })
                                ]
                            })
                        ]
                    })
                ]
            });

            visible = true;

            $("body").append(modal);
        }
    };

    return modalObj;
}
