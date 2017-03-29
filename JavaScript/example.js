var DT;

function showAlert($type, $message) {
    $('#alert_message').removeClass("alert-danger").removeClass('alert-success');
    $('#alert_message').parents('.row').removeClass("hidden");
    $('#alert_message').addClass('alert-' + $type).html($message);
}

function confirmModal(body, callback) {
    $('#confirm_modal').find('.modal-body').text(body);
    $('#confirm_modal').modal('show');
    $('#confirm_modal button[data-false]').off('click').click(function() {
        $('#confirm_modal').modal('hide');
        callback(false);
    });
    $('#confirm_modal button[data-true]').off('click').click(function() {
        $('#confirm_modal').modal('hide');
        callback(true);
    });
}

function tabBind() {
    $('a[data-toggle="tab"]').on('shown.bs.tab', function(e) {
        // Remove preview tab data from DOM
        var prev_tab = $(e.relatedTarget).attr('aria-controls');
        $('[data-for=' + prev_tab + '] tbody tr').remove();
        // Load data for clicked tab
        var tabName = $(this).attr('aria-controls');
        var tab = $(this);
        $('.box-body .active .my-data-table thead th:first').remove();
        _dataTable(tabName);
    });
}

function moveToTrashEvent() {
    $('.work-panel button[data-action=to_trash]').off('click').click(function(e) {
        var button = $(this);
        e.preventDefault();
        confirmModal("The selected conversations will be moved to trash.", function(is_true) {
            if (is_true) {
                var data = "moved=" + button.parents('.tab-pane').attr('data-for');
                data += "&" + button.parents("form").serialize();
                var j = 0;
                $.ajax({
                    "url": "/index.php/coms/new_sendToTrash",
                    "method": "POST",
                    "dataType": "JSON",
                    "data": data,
                    success: function(data) {
                        if (data.status == 1) {
                            showAlert("success", data.message);
                            setDisButton(false);
                            DT._fnAjaxUpdate();
                            updateMessageCounter();
                        } else showAlert("danger", data.message);
                    },
                    error: function() {
                        showAlert("danger", "You have been logout.");
                    }
                });
            }
        });
    });
}

function markAsReadUnreadEvent() {
    $('.work-panel button[data-action=mark_read_unread]').off("click").click(function(e) {
        var button = $(this);
        e.preventDefault();
        var data = "&" + button.parents("form").serialize();
        var markAs = "read";
        button.parents("form").find('input[type=checkbox]:checked').each(function(i) {
            if ($(this).attr('value') !== undefined && $(this).parents('tr').find("td:last").hasClass("read")) markAs = "unread";
        });
        data += "&markAs=" + markAs;
        $.ajax({
            "url": "/index.php/coms/markAsReadUnread",
            "method": "POST",
            "dataType": "JSON",
            "data": data,
            success: function(data) {
                if (data.status == 1) {
                    // notify
                    updateMessageCounter();
                    setDisButton(false);
                    DT._fnAjaxUpdate();
                }
            },
            error: function() {
                showAlert("danger", "You have been logout.");
            }
        });
    });
}
// Ajax method getting counter unread message
function updateMessageCounter() {
    $.ajax({
        "url": "/index.php/coms/getCountUnreadMessages",
        "method": "POST",
        "dataType": "JSON",
        success: function(data) {
            notifyMessage(parseInt(data.unread_count));
        },
        error: function() {
            showAlert("danger", "You have been logout.");
        }
    });
}
// callback for ajax updateMessageCounter()
function notifyMessage(count) {
    var badge = $('#message_notify .combo-badge');
    var button = $('#message_notify .combo-button');
    if (button.hasClass("notify") && count == 0) button.removeClass('notify');
    else if (count != 0 && !button.hasClass("notify")) button.addClass('notify');
    if (count > 0) badge.find("span").text(count);
}

function deleteEvent() {
    $('.work-panel button[data-action=to_delete]').click(function(e) {
        var button = $(this);
        e.preventDefault();
        confirmModal("The selected conversations Delete.", function(is_true) {
            if (is_true) {
                var data = "moved=" + button.parents('.tab-pane').attr('data-for');
                data += "&" + button.parents("form").serialize();
                $.ajax({
                    "url": "/index.php/coms/new_deleteMessage",
                    "method": "POST",
                    "dataType": "JSON",
                    "data": data,
                    success: function(data) {
                        if (data.status == 1) {
                            showAlert("success", data.message);
                            setDisButton(false);
                            DT._fnAjaxUpdate()
                            updateMessageCounter();
                        } else showAlert("danger", data.message);
                    },
                    error: function() {
                        showAlert("danger", "You have been logout.");
                    }
                });
            }
        });
    });
}

function restoreEvent() {
    $('.work-panel button[data-action=to_inbox]').click(function(e) {
        var button = $(this);
        e.preventDefault();
        confirmModal("The selected conversations restore.", function(is_true) {
            if (is_true) {
                var data = "moved=" + button.parents('.tab-pane').attr('data-for');
                data += "&" + button.parents("form").serialize();
                $.ajax({
                    "url": "/index.php/coms/new_restoreMessage",
                    "method": "POST",
                    "dataType": "JSON",
                    "data": data,
                    success: function(data) {
                        if (data.status == 1) {
                            showAlert("success", data.message);
                            setDisButton(false);
                            DT._fnAjaxUpdate();
                            updateMessageCounter();
                        } else showAlert("danger", data.message);
                    },
                    error: function() {
                        showAlert("danger", "You have been logout.");
                    }
                });
            }
        });
    });
}

function accordionAjxes(el) {
    elClicked = el;
    el = el.next();
    var mess_id = $(el).find('[data-mess-id]').attr("data-mess-id");
    var tab = $(el).parents("[data-for]").attr('data-for');
    // Marking as read
    if ($(el).prev().find("td:last").hasClass("unread")) {
        if (tab == "inbox") {
            $.ajax({
                "url": "/index.php/coms/markAsRead",
                "method": "POST",
                "dataType": "JSON",
                "data": {
                    "ajax": "1",
                    "pmId": mess_id
                },
                success: function(data) {
                    if (data.status == 1) {
                        updateMessageCounter();
                        $(el).prev().find("td:last").removeClass('unread').addClass('read').text('read');
                    }
                },
                error: function() {
                    showAlert("danger", "You have been logout.");
                }
            });
        }
    }
    // get documents
    $(el).find('.files').addClass('loader-horizontal');
    $.ajax({
        "url": "/index.php/coms/getAllDocumentsByMessage",
        "method": "POST",
        "dataType": "JSON",
        "data": {
            "pmId": mess_id
        },
        success: function(data) {
            if (data.length > 0) {
                var html = "";
                for (i = 0; i < data.length; i++) {
                    html += "<br/><a data-docID = '" + data[i].document_id + "' href='/index.php/fileprocessor/getdocument/" + data[i].document_id + "' target='_blank'><i class='fa fa-paperclip'></i>&nbsp;&nbsp;" + data[i].file_name_from_user + "</a>";
                }
                $(el).find('.files').html(html);
            }
            $(el).find(".files").removeClass('loader-horizontal');
        },
        error: function() {
            showAlert("danger", "You have been logout.");
        }
    });
}

function setDisButton(disabled) {
    //enabled and disabled buttons "Delete Selected" and "Move To Trash"
    if (disabled) {
        $(".tab-pane.active").find('.work-panel button').each(function() {
            $(this).removeAttr("disabled");
        });
    } else {
        $(".tab-pane.active").find('.work-panel button').each(function() {
            $(this).attr("disabled", "");
        });
        $(".inbox-all:visible").iCheck("uncheck");
    }
}

function checkboxALL() {
    //is need for uncheck chekbox "All"
    var isBubble = false;
    $('input[type=checkbox]').bind('ifChanged', function() {
        //Bind checkbox all
        if ($(this).attr('data-type') == "all") {
            if (!isBubble) {
                var checked = $(this).prop("checked");
                $(this).parents("table").find("tbody input[type=checkbox]").each(function() {
                    if (checked) $(this).iCheck('check');
                    else $(this).iCheck('uncheck');
                });
            }
        }
        // bind another checkbox
        else {
            isBubble = true;
            if ($(this).prop("checked") == false) $(this).parents('table').find("thead input[type=checkbox]").iCheck('uncheck');
            isBubble = false;
        }
        var disabled = false;
        $(this).parents("table").find("tbody input[type=checkbox]").each(function() {
            if ($(this).prop("checked")) disabled = true;
        });
        setDisButton(disabled);
    });
}

function sendMessageBind() {
    $('#send_message, #send_to_draft').click(function(e) {
        e.preventDefault();
        var button = $(this);
        var data = $('#form_message').serialize();
        var method = button.attr('id') == "send_message" ? "new_sendPM" : "new_sendToDraft";
        // check required fields
        if ($("[name='pm_to[]'] option:selected").length == 0) {
            showAlert("danger", "Field \"Send To\" can not be empty.");
            return false;
        }
        if ($("input[name='subject']").val().length == 0) {
            showAlert("danger", "Field \"Subject\" can not be empty.");
            return false;
        }
        if ($("textarea[name=message]").val().length == 0) {
            showAlert("danger", "Field \"Message\" can not be empty.");
            return false;
        }
        preloader(true);
        $.ajax({
            "url": "/index.php/coms/" + method,
            "method": "POST",
            "dataType": "JSON",
            "data": data,
            success: function(data) {
                if (data.status == 1) showAlert("success", data.message);
                else showAlert("danger", data.message);
                cleanForm();
                preloader(false);
                DT._fnAjaxUpdate();
            },
            error: function() {
                showAlert("danger", "You have been logout.");
            }
        })
    });
}

function bindReply() {
    $('.reply-button').click(function(e) {
        e.preventDefault();
        var button = $(this);
        $('#reply_modal_message').val("");
        var message_id = button.attr('data-mess-id');
        var sender_id = button.parents('tr').attr('data-sender-id');
        //var accordion = $('.message_accordion[data-mess-id='+message_id+']');
        var subject = $(this).parents('tr').prev().find("td:nth-child(3)").text();
        var public_tocken = button.parents('tr').attr('data-public-tocken');
        $('#reply_modal_subject').val("Reply to: " + subject);
        if (public_tocken != "") {
            var public_name = $(this).parents('tr').prev().find("td:nth-child(2)").text();
            $('#reply_modal_select option:first').before("<option>" + public_name + "</option>").parent().find(":first").prop("selected", true);
        } else {
            var option_sender = $('#reply_modal_select').find("option[value=" + sender_id + "]");
            // Check isset in contact list this
            if (option_sender.length == 0) {
                showAlert("danger", "Cant find sender.");
                return false;
            }
            option_sender.prop("selected", true);
        }
        $('#reply_modal').modal('show');
        $("#reply_modal [data-true]").off('click').click(function(e) {
            e.preventDefault();
            var message = $('#reply_modal_message').val();
            var subject = $('#reply_modal_subject').val();
            if ($('#reply_modal_subject').val() == "") {
                $('#reply_modal_subject').addClass('mark-error');
                return true;
            } else $('#reply_modal_subject').removeClass('mark-error');
            if ($('#reply_modal_message').val() == "") {
                $('#reply_modal_message').addClass('mark-error');
                return true;
            } else $('#reply_modal_message').removeClass('mark-error');
            $.ajax({
                "url": "/index.php/coms/new_sendToReply",
                "method": "POST",
                "dataType": "JSON",
                "data": {
                    "message": message,
                    "subject": subject,
                    "pm_to": [sender_id],
                    "message_id": message_id,
                    "public_tocken": public_tocken
                },
                success: function(data) {
                    if (button.parents('tr').prev().find('td:last i').length == 0) button.parents('tr').prev().find('td:last').append("<i class='fa fa-reply'></i>");
                    if (data.status == 1) showAlert("success", data.message);
                    else showAlert("danger", data.message);
                    $('#reply_modal').modal('hide');
                },
                error: function() {
                    showAlert("danger", "You have been logout.");
                }
            });
        });
    });
}

function _dataTable(tab) {
    if (DT != undefined) DT.fnDestroy();
    DT = $('[data-for=' + tab + '] .my-data-table').dataTable({
        // disable search 
        "bFilter": false,
        // disable sorting
        "aoColumns": false,
        // responsive
        "bAutoWidth": false,
        "aoColumnDefs": [{
            "sWidth": "8%",
            "bSearchable": false,
            "aTargets": [0]
        }, {
            "sWidth": "12%",
            "aTargets": [3]
        }, {
            "sWidth": "9%",
            "aTargets": [4]
        }, {
            "sWidth": "10%",
            "aTargets": [5]
        }, {
            "bSearchable": false,
            "bVisible": false,
            "aTargets": [6]
        }],
        "bProcessing": true,
        "bServerSide": true,
        // url
        "sAjaxSource": "/index.php/coms/getMore",
        // add reques patam
        "fnServerParams": function(aoData) {
            // adding name of tab for correct answer 
            aoData.push({
                "name": "tabName",
                "value": tab
            });
        },
        // responce
        "fnServerData": function(sSource, aoData, fnCallback, oSettings) {
            oSettings.jqXHR = $.ajax({
                "dataType": 'json',
                "type": "POST",
                "url": sSource,
                "data": aoData,
                "success": fnCallback,
                "error": [fnErrorCallback]
            });
        },
        // function reinit
        "fnDrawCallback": function(settings) {
            if (settings.aoData.length == 0) {
                $('.dataTables_empty').attr('colspan', 7);
                return;
            }
            $('[data-for] input[type=checkbox]').iCheck({
                checkboxClass: "icheckbox_minimal"
            });
            // Mark as replied
            $('.my-data-table tbody tr').each(function() {
                $(this).find(":last").addClass($(this).find(":last").text());
                if (DT.fnGetData($(this)[0]) != null) // похоже на костыль TODO
                    if ($(this).find('td:last i').length == 0 && DT.fnGetData($(this)[0])[7] == 1) $(this).find('td:last').append("<i class='fa fa-reply'></i>");
                if (tab == "draft") {
                    $(this).find(":last").html("<button class='btn btn-warning btn-xs send-draft'>Edit</button>");
                }
            });
            // Toggle accordion
            $(".my-data-table tbody tr").off("click").click(function() {
                var tr = $(this)[0];
                if (!DT.fnIsOpen(tr)) {
                    DT.fnOpen(tr, DT.fnGetData(tr)[6], 'my-details');
                    $('.my-details').attr('colspan', 7);
                    $('.my-details').find("table").show();
                    if (tab != "inbox") $('.reply-button').hide();
                    else bindReply();
                    accordionAjxes($(this));
                } else {
                    DT.fnClose(tr);
                }
            });
            if ($('.box-body .active .my-data-table thead tr').find(":first").html() != "") $('.box-body .active .my-data-table thead tr').find(":first").before("<th></th>");
            checkboxALL();
            deleteEvent();
            restoreEvent();
            markAsReadUnreadEvent();
            moveToTrashEvent();
            reinitLabel();
            resendDraft();
        }
    });
}

function fnErrorCallback(err, err2, err3) {
    showAlert("danger", "You have been logout.");
}

function slideMessageBox() {
    $('.slideMessageBox').click(function() {
        if (!$(".message-box").is(":visible")) $(".message-box").slideDown(400, function() {
            if ($("body").width() < 1185) {
                $("body").animate({
                    'scrollTop': $(".message-box").offset().top - $('.header').height() - 20
                }, 'slow');
            }
        });
        else {
            if ($("body").width() < 1185) {
                $("body").animate({
                    'scrollTop': $(".message-box").offset().top - $('.header').height() - 20
                }, 'slow');
            }
        }
    });
    $('#message_close').click(function() {
        $(".message-box").slideUp();
        cleanForm();
    });
}

function cleanForm() {
    $("#form_message")[0].reset()
    $("#form_message").find('option').prop('selected', false).parent().trigger('chosen:updated');
}
var interval;

function preloader(on) {
    if (on == true) {
        $(".blocked").fadeIn(300);
        $("#sending_preloader").removeClass('hidden');
        var count = 1;
        var dotted = '';
        interval = setInterval(function() {
            for (i = 0; i < count; i++) $("#sending_preloader .dotted").append(".");
            count++;
            if (count == 4) {
                $("#sending_preloader .dotted").text("");
                count = 1;
                dotted = '';
            }
        }, 800);
    } else {
        $(".blocked").stop(true, true).fadeOut(300, function() {
            $(".message-box").slideUp();
            $("body").animate({
                'scrollTop': 0
            }, 'slow');
            $("#sending_preloader").addClass('hidden');
            clearInterval(interval);
        });
    }
}

function initSendToChosen() {
    $("select[name^=pm_to], select[name^=document]").chosen({
        width: "100%"
    });
}

function reinitSendToChosen() {
    $("select[name^=pm_to], select[name^=document]").trigger('chosen:updated');
}

function reinitSendToChosen() {
    $("select[name^=pm_to], select[name^=document]").trigger('chosen:updated');
}

function reinitLabel() {
    $('[for=inbox-all]').off("click").click(function() {
        $('.inbox-all:visible').iCheck("toggle");
    });
}

function resendDraft() {
    $(".send-draft").off("click").click(function(e) {
        e.preventDefault();
        e.stopPropagation();
        var tr = $(this).parents("tr");
        var mess_id = tr.find("input").val();
        $.ajax({
            "url": "/index.php/coms/getMessageById",
            "method": "POST",
            "dataType": "JSON",
            "data": {
                "message-id": mess_id
            },
            success: function(data) {
                cleanForm();
                setRightBlock({
                    sendTo: data.to_user_id,
                    documents: data.documents,
                    subject: data.subject,
                    message: data.message,
                    message_id: data.id
                });
                // show right block
                if (!$(".message-box").is(":visible")) $(".message-box").slideDown();
            },
            error: function() {
                showAlert("danger", "You have been logout.");
            }
        });
    });
}

function setRightBlock(messageObj) {
    $("#form_message [name='pm_to[]'] option[value=" + messageObj.sendTo + "]").prop("selected", true);
    for (var docID in messageObj.documents)
        if (!messageObj.hasOwnProperty(docID)) {
            $("#form_message select[name='document[]'] option[value='" + messageObj.documents[docID].document_id + "']").prop("selected", true);
        }
    $("#form_message [name=subject]").val(messageObj.subject);
    $("#form_message [name=message]").val(messageObj.message);
    $("#form_message [name=message-id]").val(messageObj.message_id);
    reinitSendToChosen();
}
$(document).ready(function() {
    _dataTable("inbox");
    tabBind();
    sendMessageBind();
    slideMessageBox();
    initSendToChosen();
});