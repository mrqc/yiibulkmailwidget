<?php
	class BulkMail extends CInputWidget {

		//internal
		private $formId = "";
		private $container = "";
		private $entryNameContainer = "";
		private $inputFieldNew = "";
		private $inputFieldEdit = "";
		private $formName = "";
		private $editInputFieldCssClass = "editInputField";

		//optional
		public $regEx = "/([a-zA-Z0-9._-]+@[a-zA-Z0-9._-]+\.[a-zA-Z0-9._-]+)/gi";
		public $cssEntry = "background-color: #FFEFB2; border: 1px solid #EEEEEE; display: inline-block; padding: 2px; margin: 2px;";
		public $cssInputNew = "border: 0px solid; width: 98%; padding: 2px; font-size: 16px;";
		public $cssContainer = "border: 1px solid #9b9b9b;";
		public $cssEditInputField = "";

		// mandatory fields
		public $field = NULL;
		public $form = NULL;

		public function init() {
			parent::init();
			if ($this->field == NULL || $this->form == NULL || $this->model == NULL) {
				die("BulkMail: please specify the paramter 'field', 'form' and 'model'");
			}
			$this->formId = $this->form->id;
			$this->container = $this->formId . "_bulkEmailList";
			$this->entryNameContainer = $this->formId . "_emailEntry";
			$this->inputFieldNew = $this->formId . "_bulkEmails";
			$this->inputFieldEdit = $this->formId . "emailEditTextField";
			$this->formName = get_class($this->model);
		}

		public function run() {
			parent::run();
			$emailsString = (isset($this->model->emails)) ? implode(' ', $this->model->emails) : "";
			$jsCode = <<<JS
				var BulkMail = new Object();
				BulkMail.emailsCount = 0;
				BulkMail.removeMailEntry = function(obj) {
					$("#{$this->entryNameContainer}_" + obj).remove();
				}

				BulkMail.saveEditEmailForm = function(divIndex) {
					var email = $("#{$this->inputFieldEdit}_" + divIndex).val();
					$("#{$this->entryNameContainer}_" + divIndex).html(
						email + " [ <a href='javascript:BulkMail.removeMailEntry(" + divIndex + ")'>X</a> ]" + 
						"<input type='hidden' value='" + email + "' name='{$this->formName}[{$this->field}][]'>"
					);
					window.setTimeout(function() {
						$("#{$this->entryNameContainer}_" + divIndex).click(function() {
							BulkMail.showEditEmailForm(divIndex, email);
						});
					}, 10);
				}
				
				BulkMail.showEditEmailForm = function(divIndex, email) {
					$("#{$this->entryNameContainer}_" + divIndex).removeAttr("onClick");
					$("#{$this->entryNameContainer}_" + divIndex).unbind("click");
					$("#{$this->entryNameContainer}_" + divIndex).html(
						"<input type='text' value='" + email + "' name='{$this->formName}[{$this->field}][]' size='30' id='{$this->inputFieldEdit}_" + divIndex + "' class='{$this->editInputFieldCssClass}'>" +
						"<input type='button' value='OK' onClick='BulkMail.saveEditEmailForm(" + divIndex + ")'>"
					);
				}

				BulkMail.processBulkEmails = function(value) {
					var emails = this.findEmailAddresses(value);
					if (emails != null) {
						for(var emailIndex = 0; emailIndex < emails.length; emailIndex++) {
							this.emailsCount++;
							$("#{$this->container}Content").append(
								"<div id='{$this->entryNameContainer}_" + this.emailsCount + "' class='{$this->entryNameContainer}' onClick='BulkMail.showEditEmailForm(" + this.emailsCount + ", \"" + emails[emailIndex] + "\")'>" +    
									emails[emailIndex] + " [ <a href='javascript:BulkMail.removeMailEntry(" + this.emailsCount + ")'>X</a> ]" + 
									"<input type='hidden' value='" + emails[emailIndex] + "' name='{$this->formName}[{$this->field}][]'>" +
								"</div>");
						}
						$("#{$this->inputFieldNew}").val("");
					}
				}

				BulkMail.findEmailAddresses = function(value) {
					var separateEmailsBy = ", ";
					var emailsArray = value.match({$this->regEx});
					if (emailsArray) {
						return emailsArray;
					}
					return null;
				}
				$(document).ready(function() {
					$("#{$this->inputFieldNew}").bind('paste', function(e) {
						var element = $(this);
						setTimeout(function() {
							var text = $(element).val();
							BulkMail.processBulkEmails(text);
						}, 100);
					});
					$("#{$this->inputFieldNew}").keyup(function(e) {
						if (e.which == 32 || e.which == 13) {
							BulkMail.processBulkEmails($("#{$this->inputFieldNew}").val());
						}
					});
					$(window).keydown(function(event){
						if(event.keyCode == 13) {
							event.preventDefault();
							return false;
						}
					});
					$("#{$this->formId}").submit(function() {
						BulkMail.processBulkEmails($("#{$this->inputFieldNew}").val());
						return true;
					});
					BulkMail.processBulkEmails("{$emailsString}");
				});
JS;
			Yii::app()->clientScript->registerScript('BulkMailJavaScript', $jsCode, CClientScript::POS_BEGIN);
			$cssCode = <<<CSS
				#{$this->container} .{$this->entryNameContainer} {
					{$this->cssEntry}
				}

				#{$this->container} #{$this->inputFieldNew} {
					{$this->cssInputNew}
				}

				#{$this->container} {
					{$this->cssContainer}
				}
				#{$this->container} .{$this->editInputFieldCssClass} {
					{$this->cssEditInputField};
				}
CSS;
			Yii::app()->clientScript->registerCss('BulkMailCss', $cssCode, 'screen');
			$htmlCode = <<<HTML
				<div id="{$this->container}">
					<div id="{$this->container}Content"></div>
					<input type="text" name="{$this->inputFieldNew}" id="{$this->inputFieldNew}"/>
				</div>
HTML;
			echo $htmlCode;
		}
	}
?>
