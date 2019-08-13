
window.onload = function() {

	var rulesTdElement = document.querySelector("#row_carriers_brisqq_shipping_priceRules .value");
	rulesTdElement.style.width = "600px";

	var savedPriceRules = document.querySelector("#row_carriers_brisqq_shipping_priceRulesSaved");
	var savedPriceTiers = document.querySelector("#row_carriers_brisqq_shipping_partnerPriceTiers");
	if (savedPriceTiers == null) {
		return;
	}
	savedPriceTiers.style.display = "none";
	savedPriceRules.style.display = "none";

	tierObject = [];

	var accountId = document.querySelector("#carriers_brisqq_shipping_accountID");
	var brisqq_rules_button = null;

	var http = new XMLHttpRequest();
	var params = "accountId=" + accountId.value;
	http.open("POST", 'https://core-staging.brisqq.com/eapi/account', true);
	http.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	http.onreadystatechange = function() {
	    if(http.readyState == 4 && http.status == 200) {
	        obj = JSON.parse(http.responseText);
	        price_tiers = obj.tiers;
	        printing_tiers();
	        printSavedValues();
	        add_rule_print();
	        add_rule_button();
	        printSavedRules();

	    }
	}
	http.send(params);




	 brisqq_price_rules = document.querySelector("#row_carriers_brisqq_shipping_priceRules > td.value");


	function add_rule_print() {

		brisqq_price_rules.innerHTML = '<div id="add_rule_button" style="border-width: 1px; border-style: solid; border-color: #ed6502 #a04300 #a04300 #ed6502; padding: 1px 7px 2px 7px; background: #ffac47 url(images/btn_bg.gif) repeat-x 0 100%; color: #fff; font: bold 12px arial, helvetica, sans-serif; cursor: pointer; text-align: center !important; white-space: nowrap; width: 30%;">Add new rule</div>';
	}

	function add_rule_button() {
		brisqq_rules_button = document.querySelector("#add_rule_button");
		brisqq_rules_button.addEventListener("click", newRulePrint);

		var rulesExplanationTitleDiv = document.createElement("div");
		var rulesExplanationDiv = document.createElement("div");
		var sen4 = document.createTextNode(" How to create a rule ");
		var sen5 = document.createTextNode("You can set delivery discounts or price rules based on the total cart value. For example, if the cart value is greater than X amount; you can either apply a % discount to the delivery fee, increase/decrease price to a fixed amount, or alternatively set the price to 0 (i.e. free).");
		rulesExplanationDiv.className = "rulesExplanationDiv";
		rulesExplanationTitleDiv.className = "rulesExplanationTitleDiv";
		rulesExplanationTitleDiv.appendChild(sen4);
		rulesExplanationDiv.appendChild(sen5);
		brisqq_rules_button.parentNode.insertBefore(rulesExplanationDiv, brisqq_rules_button.nextSibling);
		brisqq_rules_button.parentNode.insertBefore(rulesExplanationTitleDiv, brisqq_rules_button.nextSibling);
		rulesExplanationTitleDiv.style.marginTop = "20px";
		rulesExplanationTitleDiv.style.fontWeight = "800";
	}

	function newRulePrint(cartV, op, price) {
		cartV = '';
		op= '=';
		price= '';
		newRuleLoad(cartV, op, price);
	}

	function newRuleLoad(cartV, op, price) {

		var rulesDiv = document.createElement("div");
		rulesDiv.className = "rulesDiv";

		var optionValues = ["=",">","<"];

		var selectList = document.createElement("select");
		selectList.id = "mySelect";
		selectList.className = "rulesInputs";


		var cartValue = document.createElement("input");
		cartValue.id = "cartValue";
		cartValue.type = "text";
		cartValue.className = "rulesInputs";
		cartValue.value = cartV;

		var discountPrice = document.createElement("input");
		discountPrice.id = "discountPrice";
		discountPrice.type = "text";
		discountPrice.className = "rulesInputs";
		discountPrice.value = price;

		var delButton = document.createElement("span");
		delButton.id = "delete";
		delButton.className = "brisqqDeleteButton";
		var t = document.createTextNode("Delete");
		delButton.appendChild(t);
		delButton.style.width = "30px";
		delButton.style.cursor = "pointer";
		delButton.style.backgroundColor = "#ffac47";
		delButton.style.color = "white";
		delButton.style.padding = "3px";


		var sentence1 = document.createElement("span");
		var sen1 = document.createTextNode("If the cart value is ");
		sentence1.appendChild(sen1);

		var sentence2 = document.createElement("span");
		var sen2 = document.createTextNode(" to/then £ ");
		sentence2.appendChild(sen2);

		var sentence3 = document.createElement("span");
		var sen3 = document.createTextNode(" then set delivery price or delivery discount to ");
		sentence3.appendChild(sen3);



		rulesDiv.appendChild(sentence1);
		rulesDiv.appendChild(selectList);
		rulesDiv.appendChild(sentence2);
		rulesDiv.appendChild(cartValue);
		rulesDiv.appendChild(sentence3);
		rulesDiv.appendChild(discountPrice);
		rulesDiv.appendChild(delButton);

		rulesDiv.style.paddingTop = "10px";

		brisqq_rules_button.parentNode.insertBefore(rulesDiv, brisqq_rules_button.nextSibling);


		delButton.addEventListener("click", brisqqDeleteRule, false);

		for (var i = 0; i < optionValues.length; i++) {
		    var option = document.createElement("option");
		    option.value = optionValues[i];
		    option.text = optionValues[i];
		    selectList.appendChild(option);
		}

		selectList.value = op;

		var rulesInputsElem = document.querySelectorAll(".rulesInputs");

		for (var i = 0; i < rulesInputsElem.length; i++){
			// rulesInputsElem[i]
			rulesInputsElem[i].addEventListener("change", partnerRulesInput, false);
			rulesInputsElem[i].addEventListener("keyup", partnerRulesInput, false);
		}

		var inputsRules = document.querySelectorAll(".rulesInputs");
		for (var i = 0; i < inputsRules.length; i++) {
			inputsRules[i].style.width = "40px";
			inputsRules[i].style.marginLeft = "5px";
			inputsRules[i].style.marginRight = "5px";
		}

	}


	function brisqqDeleteRule(event) {

		var parentDiv = event.target.parentNode;
		parentDiv.parentNode.removeChild(parentDiv);
		partnerRulesInput();

	}


	function printSavedRules() {

		var partners_price_tiers_field = document.querySelector('#carriers_brisqq_shipping_priceRulesSaved');

		if (partners_price_tiers_field.value) {

			valuesFirstSplit = partners_price_tiers_field.value.split("&");

			for (var i = 0; i < valuesFirstSplit.length; i++) {

				if (valuesFirstSplit[i].indexOf("=") !== -1) {
					var savedPrice = valuesFirstSplit[i].split("=");
					newRuleLoad(savedPrice[0], "=", savedPrice[1]);
				}

				if (valuesFirstSplit[i].indexOf(">") !== -1) {
					var savedPrice = valuesFirstSplit[i].split(">");
					newRuleLoad(savedPrice[0], ">", savedPrice[1]);
				}

				if (valuesFirstSplit[i].indexOf("<") !== -1) {
					var savedPrice = valuesFirstSplit[i].split("<");
					newRuleLoad(savedPrice[0], "<", savedPrice[1]);
				}


			}
		}
	}


	function partnerRulesInput() {

		var brisqq_rules_prices = document.querySelectorAll(".rulesDiv");

		var brisqq_partner_rules_serialize_price = document.querySelector("#carriers_brisqq_shipping_priceRulesSaved");

		arr1 = [];

		for (var i = 0; i < brisqq_rules_prices.length; i++) {
			var str = '';
			str += brisqq_rules_prices[i].querySelector("#cartValue").value;
			str += brisqq_rules_prices[i].querySelector("#mySelect").value;
			str += brisqq_rules_prices[i].querySelector("#discountPrice").value;

			arr1.push(str);
		}

		brisqq_partner_rules_serialize_price.value = arr1.join('&');

	}



	function printing_tiers() {
		var brisqq_tier_price = document.querySelector("#row_carriers_brisqq_shipping_priceTiers > td.value");

		var tiersprint = '';

	    for (var i = 0; i < price_tiers.length; i++) {
	      tierObject.push(price_tiers[i].distance + ' ' + price_tiers[i].price);
		  tiersprint+=
		  	'<tr>' +
			    '<td>' + price_tiers[i].distance + 'km</td>' +
			    '<td>£' + price_tiers[i].price + '</td>' +
			    '<td><input class="brisqq_price" data-distance="'+price_tiers[i].distance+'" onkeypress="return isNumber(event)" onkeyup="partnerInput()" type="text"></input></td>' +
		  	'</tr>';
		}

		brisqq_tier_price.innerHTML =
		'<table>' +
			'<tr>' +
			    '<th>Distance</th>' +
			    '<th>Price</th>' +
			    '<th>Customer-facing price</th>' +
		  	'</tr>' +
		  	tiersprint
		  '<tr>' +
		'</table>';
	}

}

function printSavedValues() {
	var brisqq_prices = document.querySelectorAll(".brisqq_price");

	var partners_price_tiers_field = document.querySelector('#carriers_brisqq_shipping_partnerPriceTiers');

	if (partners_price_tiers_field.value) {

		valuesFirstSplit = partners_price_tiers_field.value.split("&");

		for (var i = 0; i < valuesFirstSplit.length; i++) {

			var savedPrice = valuesFirstSplit[i].split("=");

			brisqq_prices[i].value = savedPrice[1];
		}
	}
}



function partnerInput() {
	var brisqq_prices = document.querySelectorAll(".brisqq_price");

	var brisqq_partner_serialize_price = document.querySelector("#carriers_brisqq_shipping_partnerPriceTiers");

	arr = [];
	for (var i = 0; i < brisqq_prices.length; i++) {
		arr.push(brisqq_prices[i].getAttribute('data-distance') + '=' + brisqq_prices[i].value);
	}
	brisqq_partner_serialize_price.value = arr.join('&');

}

function isNumber(evt) {
    evt = (evt) ? evt : window.event;
    var charCode = (evt.which) ? evt.which : evt.keyCode;
    if (charCode > 31 && (charCode < 48 || charCode > 57)) {
        return false;
    }
    return true;
}
