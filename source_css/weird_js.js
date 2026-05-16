function _typeof(a) {
  "@babel/helpers - typeof";
  return (
    (_typeof =
      "function" == typeof Symbol && "symbol" == typeof Symbol.iterator
        ? function (b) {
            return typeof b;
          }
        : function (c) {
            return c &&
              "function" == typeof Symbol &&
              c.constructor === Symbol &&
              c !== Symbol.prototype
              ? "symbol"
              : typeof c;
          }),
    _typeof(a)
  );
}
var _formConfig = null;
window.elementsActive = false;
function getFormConfig(d, e, f) {
  if (_formConfig === null) {
    var g = jQuery.extend(
      {
        locale: "ru",
        defaultCountry: "ru",
        preferredCountries: "ru,kz,by",
        onlyCountries: "ru,kz,by,ua,ee,lt,lv,uz,ge",
        nationalMode: true,
        strictMask: typeof storeConfig !== "undefined" && storeConfig.phoneMask,
      },
      typeof window.formConfig != "undefined" ? window.formConfig : {},
    );
    if (typeof window.countryId != "undefined") {
      g.defaultCountry = window.countryId;
    }
    _formConfig = g;
  }
  if (d) {
    try {
      if (!_formConfig.hasOwnProperty(d)) return e;
      var h = _formConfig[d];
      switch (f) {
        case "bool":
        case "int":
          h = parseInt(h);
          break;
      }
      return h;
    } catch (i) {
      console.log(i);
    }
  }
  return _formConfig;
}
function bindFormElements(j) {
  (function (k, l, m, n) {
    j = j || k(".aristos_form");
    j.find(".c-row").each(function () {
      var q = k(this);
    });
    j.find(".c-input, .c-textarea")
      .singleton()
      .each(function () {
        var r = k(this),
          s = r.hasClass("c-textarea"),
          t = s ? r.find("textarea") : r.find("input"),
          u = r.find(".c-status"),
          v = r.find(".fl"),
          w = t.position(),
          x = t.data("no-fl"),
          y = t.data("show-al"),
          z = r.parents(".c-row");
        if (!s && !u.length) {
          u = k("<span>").addClass("c-status").prependTo(r);
        }
        if (!v.length && !x) {
          v = k("<b>").addClass("fl").prependTo(r);
        }
        if (t.attr("required")) r.addClass("req");
        if (w) v.css("left", w.left + 10 + "px");
        var A =
          t.data("label") || t.attr("placeholder") || z.find("label").text();
        t.on("focus", function () {
          z.addClass("active");
          r.addClass("active");
          var C = r.find(".tip");
          if (C.length) {
            alignTip(C, t);
          }
        }).on("focusout", function () {
          z.removeClass("active");
          r.removeClass("active");
        });
        if (!x && !y) {
          t.on("change keyup ones", function () {
            if (empty(t.val())) {
              v.removeClass("up");
              setTimeout(function () {
                v.text("");
              }, 500);
            } else {
              v.addClass("up");
              if (v.text() == "") {
                v.html(A);
              }
            }
          });
          t.trigger("ones");
        }
        if (y) {
          v.addClass("up");
          if (v.text() === "") {
            v.html(A);
          }
        }
        if (!s && r.hasClass("with-submit")) {
          var B = k(
            '<span class="a-btn btn-input-submit animated fadeIn"><i class="fa fa-level-down fa-rotate-90"></i></span>',
          );
          t.on("keyup keypress", function (D) {
            try {
              if (D) {
                var E = D.keyCode || D.which;
                if (E == 13) {
                  t.trigger("self-submit");
                  D.preventDefault();
                  return false;
                }
              }
            } catch (D) {}
          });
          B.appendTo(r);
          B.click(function () {
            t.trigger("self-submit");
          });
        }
      });
    setTimeout(function () {
      if (typeof k.fn.intlTelInput == "function") {
        var F = getFormConfig();
        k.defaultIntlTelSettings = {
          utilsScript: "/js/lib/intl-tel-input/js/utils.js",
          defaultCountry: F.defaultCountry.toLowerCase(),
          preferredCountries: F.preferredCountries.toLowerCase().split(","),
          onlyCountries: F.onlyCountries.toLowerCase().split(","),
          nationalMode: F.nationalMode && !F.strictMask,
        };
        var G = j.find(".c-phone");
        G.singleton("on-phone").each(function () {
          var H = k(this),
            I = H.find('input[type="tel"]');
          console.info("💩 Binding form mask");
          var J = I.attr("id");
          var K = I.attr("name");
          var L = k(
            '<input type="hidden" name="' + K + '"' + ' id="' + J + '">',
          );
          var M = k('label[for="' + J + '"]');
          M.attr("for", J + "__mask");
          I.attr("id", J + "__mask")
            .attr("name", K + "__mask")
            .attr("data-target-input", J);
          I.closest(".c-phone").after(L);
          I.addClass("mask-ready");
          I.intlTelInput(k.defaultIntlTelSettings);
          I.on("keyup change", function () {
            if (I.intlTelInput("isValidNumber")) {
              var O = I.intlTelInput("getNumber");
              L.val(O);
              console.info(
                "💩 Valid number for field " + L.attr("id") + " is " + O,
              );
            } else {
              L.val("");
            }
          });
          if (!empty(I.val())) {
            setTimeout(function () {
              I.trigger("keyup");
            }, 300);
          }
          if (typeof k.validator == "function") {
            k("body")
              .singleton("add-phone-validation")
              .each(function () {
                k.validator.addMethod(
                  "phone",
                  function (P, Q) {
                    if (!l.intlTelInputUtils) return true;
                    var R = jQuery(Q);
                    if (R.intlTelInput("isValidNumber")) {
                      var S = R.intlTelInput("getNumber");
                      var T = k("#" + R.data("target-input"));
                      T.val(S);
                      console.info(
                        "💩 Valid number for " + T.attr("id") + " is " + S,
                      );
                      return true;
                    }
                    return false;
                  },
                  k.validator.messages.tel,
                );
              });
            var N = setInterval(function () {
              var U = false;
              try {
                I.rules("add", { phone: true, required: true });
                U = true;
              } catch (V) {}
              if (U) clearInterval(N);
            }, 1e3);
          }
        });
      }
    }, 500);
    j.find("button")
      .singleton()
      .on("focus", function () {
        k(this).addClass("active");
      })
      .on("blur", function () {
        k(this).removeClass("active");
      });
    j.find("label.c-radio")
      .singleton()
      .each(function (W, X) {
        if (isIE && getIEVersion() <= 8) return false;
        var Y = k(X),
          Z = Y.find("input[type='radio']");
        if (Z.length) {
          if (Z.is(":checked")) Y.addClass("active");
          Y.on("mouseover", function ($) {
            Y.addClass("focus");
          }).on("mouseout", function (_) {
            Y.removeClass("focus");
          });
          Z.on("focus", function (aa) {
            Y.addClass("focus");
          }).on("blur", function (ba) {
            Y.removeClass("focus");
          });
          Z.on("click change", function (ca) {
            var da = Y.find("input[type='radio']").attr("name");
            var ea = k('label.on.c-radio > input[name="' + da + '"]');
            ea.parent()
              .removeClass("active")
              .find("input:checked")
              .parent()
              .addClass("active");
          });
        }
      });
    j.find("label.c-checkbox")
      .singleton()
      .each(function (fa, ga) {
        if (isIE && getIEVersion() <= 8) return false;
        var ha = k(ga),
          ia = ha.find("input[type='checkbox']");
        if (ia.length) {
          var ja = function ka() {
            if (ha.find("input[type='checkbox']").is(":checked")) {
              ha.addClass("active");
            } else {
              ha.removeClass("active");
            }
          };
          if (ia.is(":checked")) ha.addClass("active");
          ha.on("mouseover", function (la) {
            ha.addClass("focus");
          }).on("mouseout", function (ma) {
            ha.removeClass("focus");
          });
          ia.on("focus", function (na) {
            ha.addClass("focus");
          }).on("blur", function (oa) {
            ha.removeClass("focus");
          });
          ja();
          ia.on("click change", function (pa) {
            if (pa.type == "click")
              if (k.browser && k.browser.msie && k.browser.version <= 8)
                ia.trigger("change");
            ja();
          });
        }
      });
    j.find("fieldset")
      .singleton("on-tabs")
      .each(function () {
        var qa = k(this),
          ra = qa.find(".legend .select"),
          sa = null;
        ra.each(function () {
          var ta = k(this),
            ua = ta.data("toggle");
          if (!empty(ua)) {
            var va = k("#" + ua);
            if (va.length) {
              ta.addClass("valid noselect");
              ta.click(function () {
                if (!ta.hasClass("ready")) return;
                if (sa) clearTimeout(sa);
                var wa = va.parent(),
                  xa = qa.find(".tab").filter(":visible");
                ra.filter(".valid").addClass("ready").removeClass("active");
                ta.removeClass("ready").addClass("active").trigger("tab.click");
                qa.find(".tab").filter(":visible").fadeOut(500);
                sa = setTimeout(function () {
                  va.fadeIn().trigger("tab.toggled");
                  ta.trigger("tab.toggled");
                }, 600);
                if (k(".authorization-form__top .c-switch").length) {
                  k(".c-switch__label").removeClass("active");
                }
              });
            }
          }
        });
      });
    j.find('[data-toggle="row"]')
      .singleton()
      .each(function () {
        var ya = k(this);
        var za = ya.data("target");
        if (za) {
          var Aa = k(za);
          if (Aa.length) {
            ya.click(function () {
              var Ba = false;
              if (ya.is('[type="checkbox"]')) {
                Ba = ya.is(":checked");
              } else if (ya.is('[type="radio"]')) {
                Ba = ya.is(":selected");
              } else {
                Ba = ya.hasClass("toggled");
              }
              ya.toggleClass("toggled");
              if (Ba) {
                Aa.addClass("active");
                Aa.fadeIn(function () {
                  if (ya.data("focus")) {
                    var Ca = k(ya.data("focus"));
                    if (Ca.length) {
                      Ca.focus();
                    }
                  }
                  k(m).trigger("aristos:toggle-row:show", {
                    el: ya,
                    target: Aa,
                  });
                  ya.trigger("aristos:toggle-row:show");
                });
              } else {
                Aa.removeClass("active");
                Aa.fadeOut();
                k(m).trigger("aristos:toggle-row:hide", { el: ya, target: Aa });
                ya.trigger("aristos:toggle-row:hide");
              }
            });
          }
        }
      });
    j.find("ul.blocks.selectable").each(function () {
      var Da = k(this);
      Da.find("li")
        .singleton("on-block")
        .click(function (Ea) {
          k(this)
            .parent()
            .find("#map-address-input-cont")
            .addClass("demo-test");
          if (
            typeof Ea.target.dataset.preventBuble !== "undefined" ||
            typeof Ea.target.dataset.preventbuble !== "undefined"
          )
            return;
          var Fa = k(this);
          Da.trigger("block.item.clicked", Fa);
          if (
            Fa.hasClass("disabled") ||
            Fa.hasClass("error") ||
            Fa.hasClass("active")
          )
            return;
          Da.find("li.active").removeClass("active");
          Fa.addClass("active");
          Da.trigger("block.item.selected", Fa);
          Fa.trigger("block.item.selected", Fa);
          if (Fa.data("id")) Fa.trigger("block.id.selected", Fa.data("id"));
          if ((autoFill = Da.data("fill-on-select")) && Fa.data("id")) {
            k(autoFill).val(Fa.data("id"));
          }
        });
    });
    if (
      typeof l.elementsActive != "undefined" &&
      !l.elementsActive &&
      j.find("ul.blocks.selectable").length
    ) {
      var o = j.find("ul.blocks.selectable");
      k(m).trigger("aristos:form-block:select", o);
    }
    if (k.validator && !k.validator.prototype._localized) {
      var p = k.validator.prototype.showLabel;
      k.validator.prototype.showLabel = function (Ga, Ha) {
        return p.call(this, Ga, Ha ? __(Ha, "baseMessages") : Ha);
      };
      k.validator.prototype._localized = true;
    }
    if (typeof j.validate != "undefined" && j.is("form")) {
      j.validate({
        ignore: ".ignore",
        errorPlacement: function Ia(Ja, Ka) {
          if (
            getFormConfig("packageTheme", "", "string") === "ariflex" &&
            (getFormConfig("mapType", "", "string") === "simple_ddt" ||
              getFormConfig("mapType", "", "string") === "google")
          ) {
            var La = Ka.closest(".c-input");
            var Ma = La.find(".check-failed");
            var Na = __(Ja.text(), "baseMessages");
            if (Ma.length) {
              if (Ma.text() !== Na) {
                Ma.text(Na);
              }
            } else {
              La.append('<span class="check-failed">' + Na + "</span>");
            }
          } else if (
            !k("#delivery-fieldset").length ||
            getFormConfig("showHints", true, "bool")
          ) {
            var Oa = Ka.closest(".c-input");
            if (!Oa.length) Oa = Ka.parent();
            Ja.text(__(Ja.text(), "baseMessages"));
            Ja.appendTo(Oa);
          }
        },
        highlight: function Pa(Qa, Ra, Sa) {
          var Ta = k(Qa),
            Ua = Ta.closest(".c-input"),
            Va = Ua.find("label[for=" + Qa.id + "]");
          if (
            Ta.attr("id") != "company_account" ||
            Ta.attr("id") != "register-birthdate"
          ) {
            if (!Ua.length) Ua = Ta;
            Ua.addClass(Ra).removeClass(Sa);
            Va.addClass(Ra);
            alignTip(Va, Ta);
          }
        },
        unhighlight: function Wa(Xa, Ya, Za) {
          var $a = k(Xa),
            _a = $a.closest(".c-input");
          if ($a.attr("id") != "company_account") {
            if (!_a.length) _a = $a;
            _a.removeClass(Ya);
            if (!empty($a.val())) _a.addClass(Za);
            if (Xa.id)
              _a.find(".c-input label[for=" + Xa.id + "]").removeClass(Ya);
          }
        },
        checkCustomerName: function ab(bb, cb, db) {
          var eb = k(bb),
            fb = eb.closest(".c-input");
          if (eb.attr("id") === "address-name") {
            if (checkAddressName(eb.val())) {
              fb.addClass(db);
              if (bb.id)
                fb.find(".c-input label[for=" + bb.id + "]").removeClass(cb);
            } else {
              fb.addClass(cb).removeClass(db);
              if (bb.id)
                fb.find(".c-input label[for=" + bb.id + "]")
                  .addClass(cb)
                  .removeClass(db);
            }
          }
        },
      });
    }
  })(jQuery, window, document);
}
function checkAddressName(gb) {
  var hb = gb;
  var ib = hb.split(" ");
  var jb = false;
  var kb = false;
  if (ib[0].length < 3) {
    jb = false;
  } else {
    jb = true;
  }
  if (ib[1].length < 3) {
    kb = false;
  } else {
    kb = true;
  }
  if (!jb || !kb) {
    return false;
  } else {
    return true;
  }
}
function alignTip(lb, mb) {
  var nb = mb.position(),
    ob = mb.offset() ? mb.offset().left : 0,
    pb = jQuery(window).width();
  if (ob + mb.width() + lb.width() > pb) {
    lb.addClass("left");
  } else {
    lb.removeClass("left");
  }
  lb.css(
    "top",
    parseInt(nb.top + mb.parent().height() / 2 - lb.innerHeight() / 2) + "px",
  );
}
(function (qb, rb, sb, tb) {
  qb.fn.extend({
    setError: function ub(vb) {
      var wb = this;
      wb.attr("error", vb).valid();
      var xb = wb.closest(".c-input").find("label.error:visible");
      alignTip(xb, wb);
      return this;
    },
    clearError: function yb() {
      try {
        this.removeAttr("error").valid();
        return this;
      } catch (zb) {
        console.log("Valid is not a function");
        this.removeAttr("error");
      }
    },
    fillValue: function Ab(Bb) {
      try {
        this.val(Bb)
          .trigger("change")
          .closest(".c-input")
          .addClass("animated pulse");
        this.valid();
        return this;
      } catch (Cb) {
        console.log("Valid is not a function");
        this.val(Bb)
          .trigger("change")
          .closest(".c-input")
          .addClass("animated pulse");
      }
    },
    formPost: function Db(Eb, Fb, Gb, Hb) {
      if (!Eb) throw new qb.error("Empty URL for request");
      Hb = qb.extend(
        {
          callbackOnError: false,
          focusOnError: true,
          block: true,
          statusObj: false,
          errorMessage: __("Request Error", "baseMessages"),
          loadingMessage: __("Please wait", "baseMessages") + "...",
          successMessage: __("Successfully", "baseMessages") + "!",
        },
        Hb,
      );
      var Ib = this,
        Jb = Ib.hasClass("c-input"),
        Kb =
          _typeof(Hb.statusObj) == "object" && Hb.statusObj.length
            ? true
            : false,
        Lb;
      if (Hb.block) {
        Lb = Ib.find("input, select, textarea, button");
        Lb.disable();
      }
      if (Jb) {
        var Mb = Ib;
        var Nb = Mb.find("input");
        Mb.addClass("ajax");
        Nb.clearError();
      }
      if (Kb) {
        var Ob = Hb.statusObj;
        Ob.html('<span class="ajax">' + Hb.loadingMessage + "</span>");
      }
      return qb
        .post(
          Eb,
          Fb,
          function (Pb) {
            if (_typeof(Pb) != "object" || Pb == null)
              Pb = { error: Hb.errorMessage };
            if (Pb.error) {
              if (Jb) {
                Nb.setError(Pb.error);
                if (Hb.focusOnError)
                  setTimeout(function () {
                    Nb.focus();
                  }, 100);
              }
              if (Kb) Ob.html('<span class="error">' + Pb.error + "</span>");
              if (Hb.callbackOnError)
                Gb({ error: Pb.error, code: Pb.code, res: Pb });
            } else {
              if (Kb) Ob.html('<span class="success"></span>');
              if (Gb && typeof Gb == "function") Gb(Pb);
            }
          },
          "json",
        )
        .fail(function (Qb, Rb, Sb) {
          if (Jb) {
            Nb.setError(Hb.errorMessage);
            if (Hb.focusOnError)
              setTimeout(function () {
                Nb.focus();
              }, 100);
          }
          if (Kb) Ob.html('<span class="error">' + Hb.errorMessage + "</span>");
          if (Hb.callbackOnError)
            Gb({ error: Hb.errorMessage, code: Rb, res: null });
        })
        .always(function () {
          if (Jb) Mb.removeClass("ajax");
          if (Hb.block) {
            Lb.enable();
          }
        });
    },
    addRow: function Tb(Ub, Vb, Wb) {
      Wb = qb.extend(
        {
          class: "",
          fa: null,
          tip: null,
          id: Ub,
          name: Ub,
          type: "text",
          placeholder: Vb,
          required: false,
        },
        Wb,
      );
      if (Wb.req) Wb.required = true;
      if (_typeof(Wb["class"]) == "object" && Wb["class"].hasOwnProperty(0))
        Wb["class"] = Wb["class"].join(" ");
      if (Wb.fa) Wb["class"] += " prep";
      var Xb = this.addInput(Ub, Vb, Wb);
      var Yb = qb('<div id="' + Ub + '-row" class="c-row">');
      if (Wb.required) Vb += " <b>*</b>";
      Yb.append(
        '<label class="control-label" for="' + Ub + '">' + Vb + "</label>",
      );
      Yb.append('<div class="controls"></div>');
      Yb.find(".controls").append(Xb);
      this.append(Yb);
      bindFormElements(this);
      return Yb;
    },
    addInput: function Zb($b, _b, ac) {
      ac = qb.extend(
        {
          class: "",
          fa: null,
          tip: null,
          id: $b,
          name: $b,
          type: "text",
          placeholder: _b,
          beforeInsert: null,
          afterInsert: null,
        },
        ac,
      );
      if (_typeof(ac["class"]) == "object" && ac["class"].hasOwnProperty(0))
        ac["class"] = ac["class"].join(" ");
      if (ac.fa) ac["class"] += " prep";
      var bc = qb('<div id="' + $b + '-cont" class="' + ac["class"] + '">');
      if (ac.fa)
        bc.append(
          '<span class="addon"><i class="fa fa-' + ac.fa + '"></i></span> ',
        );
      var cc;
      var dc = [
        "name",
        "disabled",
        "autocomplete",
        "placeholder",
        "min",
        "max",
        "minlength",
        "maxlength",
      ];
      switch (ac.type) {
        case "textarea":
          dc.push("rows");
          bc.addClass("c-textarea clear");
          cc = qb('<textarea id="' + ac.id + '">');
          break;
        case "select":
          bc.addClass("c-input");
          cc = qb('<select id="' + ac.id + '"></select>');
          if (ac.hasOwnProperty("optionValues")) {
            qb.each(ac.optionValues, function (ec, fc) {
              if (!fc) fc = ec;
              cc.append(qb('<option value="' + ec + '">' + fc + "</option>"));
            });
          }
          break;
        default:
          bc.addClass("c-input");
          cc = qb('<input id="' + ac.id + '" type="' + ac.type + '">');
      }
      bc.append(cc);
      if (ac.tip) bc.append('<p class="tip">' + ac.tip + "</p>");
      qb.each(ac, function (gc, hc) {
        if (in_array(_typeof(hc), ["string", "number"])) {
          if (dc.in_array(gc) || gc.match(/^(data|aria)-/)) cc.attr(gc, hc);
        }
      });
      if (!empty(ac.value)) cc.val(ac.value);
      if (ac.req || ac.required) cc.attr("required", "required");
      if (ac.readonly) cc.attr("readonly", "readonly");
      if (ac.beforeInsert) {
        this.before(bc);
      } else if (ac.afterInsert) {
        this.after(bc);
      } else {
        this.append(bc);
      }
      bindFormElements(this);
      qb(sb).trigger("aristos.input.addInput", bc);
      return bc;
    },
  });
  qb(sb).on("pjax:success footer:ready", function () {
    var ic = qb(".aristos_form");
    ic.each(function () {
      var jc = qb(this);
      bindFormElements(jc);
      if (typeof qb.validator != "undefined") {
        qb.validator.addMethod("error", function (kc, lc) {
          var mc = qb(lc).attr("error");
          if (mc && mc.length) {
            qb.validator.messages["error"] = mc;
            return false;
          } else {
            return true;
          }
        });
      }
    });
  });
  qb(sb).trigger("footer:ready");
  qb(sb).ready(function () {
    var nc = qb("#form-validate"),
      oc = nc.find('button[type="submit"]'),
      pc = nc.find("input.required-entry"),
      qc = nc.find("#dob"),
      rc = nc.find("#company_account"),
      sc = nc.find(".c-switch"),
      tc =
        '<div class="error">' + "__('Required field', 'FormText')" + "</div>",
      uc = false;
    function vc() {
      nc.find(".error").length ? (uc = false) : (uc = true);
      if (uc) {
        oc.removeAttr("disabled");
        oc.removeClass("disabled");
      } else {
        oc.attr("disabled", "disabled");
        oc.addClass("disabled");
      }
    }
    if (nc.length) {
      pc.on("blur", function () {
        var wc = qb(this),
          xc = wc.val().replace(/\s/g, "");
        if (xc == "") {
          if (!wc.siblings(".error").length) {
            tc =
              '<div class="error">' +
              __("Required field", "FormText") +
              "</div>";
            wc.after(tc);
          }
        } else {
          wc.siblings(".error").remove();
        }
        vc();
      });
      if (rc.length) {
        rc.on("blur", function () {
          var yc = qb(this),
            zc = yc.val(),
            Ac = zc.length,
            Bc = 20;
          if (Ac != Bc || /\D/g.test(zc)) {
            if (!yc.siblings(".error").length) {
              tc =
                '<div class="error">' +
                __("Wrong payment account", "FormText") +
                "</div>";
              yc.after(tc);
            }
          } else {
            yc.siblings(".error").remove();
          }
          vc();
        });
      }
      if (sc.length) {
        sc.on("change", function () {
          vc();
        });
      }
      if (qc.length) {
        qc.on("blur", function () {
          var Cc = qb(this),
            Dc = Cc.val(),
            Ec = Dc.split("/"),
            Fc = Ec.length - 1,
            Gc = Ec[Fc].replace(/_/g, ""),
            Hc = Gc.split("");
          if (Dc !== "") {
            if (
              Hc.length !== 4 ||
              new Date().getYear() < new Date(Gc).getYear()
            ) {
              if (!Cc.siblings(".error").length) {
                tc =
                  '<div class="error">' +
                  __("Invalid date format", "FormText") +
                  "</div>";
                Cc.after(tc);
              }
            } else {
              Cc.siblings(".error").remove();
            }
          }
          vc();
        });
      }
    }
  });
})(jQuery, window, document);

window.storeConfig = {
  swiperScriptLink:
    "//cdn.aristosgroup.ru/libs/swiper/swiper-bundle-11.0.6.min.js",
  swiperStyleLink:
    "//cdn.aristosgroup.ru/libs/swiper/swiper-bundle-11.0.6.min.css",
  addToCartPopupTitle: "Вы добавили товар в корзину",
  addToCartPopupCardBtnClass: "button filled primary medium",
  addToCartPopupCloseTimeout: 4e3,
  popupClosePosition: "inside",
  popupIsCloseOnTouch: true,
  modalType: "tingle",
  compareLink: "/item-compare/",
  bundleSettings: {
    labelTitle: "Идеальный комплект",
    labelAddToCart: "Купить комплект",
    showBundleSku: true,
    showBundleCatName: false,
    showBundleTotalsDiscount: true,
    showGroupSavings: true,
    showOptionsSku: true,
    showBundlePagination: false,
    swiperBreakpoints: { 0: { spaceBetween: 24 } },
  },
  insertConfigPriceTemplate: function a(b, c, d, e, f) {
    var g = 0;
    if (f && e) {
      g = Math.round(((f - e) * 100) / f);
    }
    return '\n        <div class="price-wrap">\n            <span class="special">'
      .concat(b, '</span>\n            <del class="old">')
      .concat(
        c,
        '</del>\n        </div>\n        <span class="savings">\n            <span class="savings__label">-',
      )
      .concat(g, "%</span>\n        </span>\n    ");
  },
  defaultMapOptions: { center: [55.6855, 37.5736], zoom: 10, controls: [] },
  defaultIcon: {
    iconImageHref:
      "data:image/svg+xml;base64," +
      btoa(
        '\n            <svg width="48" height="48" viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg">\n                <rect width="48" height="48" rx="24" fill="#232528"/>\n                <circle cx="24" cy="24" r="7.2" fill="white"/>\n            </svg>\n        ',
      ),
    iconImageSize: [30, 30],
    iconImageOffset: [-15, -15],
  },
};

function _typeof(a) {
  "@babel/helpers - typeof";
  return (
    (_typeof =
      "function" == typeof Symbol && "symbol" == typeof Symbol.iterator
        ? function (b) {
            return typeof b;
          }
        : function (c) {
            return c &&
              "function" == typeof Symbol &&
              c.constructor === Symbol &&
              c !== Symbol.prototype
              ? "symbol"
              : typeof c;
          }),
    _typeof(a)
  );
}
function _regeneratorRuntime() {
  _regeneratorRuntime = function F() {
    return d;
  };
  var d = {},
    e = Object.prototype,
    f = e.hasOwnProperty,
    g =
      Object.defineProperty ||
      function (G, H, I) {
        G[H] = I.value;
      },
    h = "function" == typeof Symbol ? Symbol : {},
    i = h.iterator || "@@iterator",
    j = h.asyncIterator || "@@asyncIterator",
    k = h.toStringTag || "@@toStringTag";
  function l(J, K, L) {
    return (
      Object.defineProperty(J, K, {
        value: L,
        enumerable: !0,
        configurable: !0,
        writable: !0,
      }),
      J[K]
    );
  }
  try {
    l({}, "");
  } catch (M) {
    l = function N(O, P, Q) {
      return (O[P] = Q);
    };
  }
  function m(R, S, T, U) {
    var V = S && S.prototype instanceof p ? S : p,
      W = Object.create(V.prototype),
      X = new C(U || []);
    return (g(W, "_invoke", { value: y(R, T, X) }), W);
  }
  function n(Y, Z, $) {
    try {
      return { type: "normal", arg: Y.call(Z, $) };
    } catch (_) {
      return { type: "throw", arg: _ };
    }
  }
  d.wrap = m;
  var o = {};
  function p() {}
  function q() {}
  function r() {}
  var s = {};
  l(s, i, function () {
    return this;
  });
  var t = Object.getPrototypeOf,
    u = t && t(t(D([])));
  u && u !== e && f.call(u, i) && (s = u);
  var v = (r.prototype = p.prototype = Object.create(s));
  function w(aa) {
    ["next", "throw", "return"].forEach(function (ba) {
      l(aa, ba, function (ca) {
        return this._invoke(ba, ca);
      });
    });
  }
  function x(da, ea) {
    function fa(ha, ia, ja, ka) {
      var la = n(da[ha], da, ia);
      if ("throw" !== la.type) {
        var ma = la.arg,
          na = ma.value;
        return na && "object" == _typeof(na) && f.call(na, "__await")
          ? ea.resolve(na.__await).then(
              function (oa) {
                fa("next", oa, ja, ka);
              },
              function (pa) {
                fa("throw", pa, ja, ka);
              },
            )
          : ea.resolve(na).then(
              function (qa) {
                ((ma.value = qa), ja(ma));
              },
              function (ra) {
                return fa("throw", ra, ja, ka);
              },
            );
      }
      ka(la.arg);
    }
    var ga;
    g(this, "_invoke", {
      value: function sa(ta, ua) {
        function va() {
          return new ea(function (wa, xa) {
            fa(ta, ua, wa, xa);
          });
        }
        return (ga = ga ? ga.then(va, va) : va());
      },
    });
  }
  function y(ya, za, Aa) {
    var Ba = "suspendedStart";
    return function (Ca, Da) {
      if ("executing" === Ba) throw new Error("Generator is already running");
      if ("completed" === Ba) {
        if ("throw" === Ca) throw Da;
        return E();
      }
      for (Aa.method = Ca, Aa.arg = Da; ; ) {
        var Ea = Aa.delegate;
        if (Ea) {
          var Fa = z(Ea, Aa);
          if (Fa) {
            if (Fa === o) continue;
            return Fa;
          }
        }
        if ("next" === Aa.method) Aa.sent = Aa._sent = Aa.arg;
        else if ("throw" === Aa.method) {
          if ("suspendedStart" === Ba) throw ((Ba = "completed"), Aa.arg);
          Aa.dispatchException(Aa.arg);
        } else "return" === Aa.method && Aa.abrupt("return", Aa.arg);
        Ba = "executing";
        var Ga = n(ya, za, Aa);
        if ("normal" === Ga.type) {
          if (((Ba = Aa.done ? "completed" : "suspendedYield"), Ga.arg === o))
            continue;
          return { value: Ga.arg, done: Aa.done };
        }
        "throw" === Ga.type &&
          ((Ba = "completed"), (Aa.method = "throw"), (Aa.arg = Ga.arg));
      }
    };
  }
  function z(Ha, Ia) {
    var Ja = Ia.method,
      Ka = Ha.iterator[Ja];
    if (undefined === Ka)
      return (
        (Ia.delegate = null),
        ("throw" === Ja &&
          Ha.iterator["return"] &&
          ((Ia.method = "return"),
          (Ia.arg = undefined),
          z(Ha, Ia),
          "throw" === Ia.method)) ||
          ("return" !== Ja &&
            ((Ia.method = "throw"),
            (Ia.arg = new TypeError(
              "The iterator does not provide a '" + Ja + "' method",
            )))),
        o
      );
    var La = n(Ka, Ha.iterator, Ia.arg);
    if ("throw" === La.type)
      return (
        (Ia.method = "throw"),
        (Ia.arg = La.arg),
        (Ia.delegate = null),
        o
      );
    var Ma = La.arg;
    return Ma
      ? Ma.done
        ? ((Ia[Ha.resultName] = Ma.value),
          (Ia.next = Ha.nextLoc),
          "return" !== Ia.method &&
            ((Ia.method = "next"), (Ia.arg = undefined)),
          (Ia.delegate = null),
          o)
        : Ma
      : ((Ia.method = "throw"),
        (Ia.arg = new TypeError("iterator result is not an object")),
        (Ia.delegate = null),
        o);
  }
  function A(Na) {
    var Oa = { tryLoc: Na[0] };
    (1 in Na && (Oa.catchLoc = Na[1]),
      2 in Na && ((Oa.finallyLoc = Na[2]), (Oa.afterLoc = Na[3])),
      this.tryEntries.push(Oa));
  }
  function B(Pa) {
    var Qa = Pa.completion || {};
    ((Qa.type = "normal"), delete Qa.arg, (Pa.completion = Qa));
  }
  function C(Ra) {
    ((this.tryEntries = [{ tryLoc: "root" }]),
      Ra.forEach(A, this),
      this.reset(!0));
  }
  function D(Sa) {
    if (Sa) {
      var Ta = Sa[i];
      if (Ta) return Ta.call(Sa);
      if ("function" == typeof Sa.next) return Sa;
      if (!isNaN(Sa.length)) {
        var Ua = -1,
          Va = function Wa() {
            for (; ++Ua < Sa.length; )
              if (f.call(Sa, Ua))
                return ((Wa.value = Sa[Ua]), (Wa.done = !1), Wa);
            return ((Wa.value = undefined), (Wa.done = !0), Wa);
          };
        return (Va.next = Va);
      }
    }
    return { next: E };
  }
  function E() {
    return { value: undefined, done: !0 };
  }
  return (
    (q.prototype = r),
    g(v, "constructor", { value: r, configurable: !0 }),
    g(r, "constructor", { value: q, configurable: !0 }),
    (q.displayName = l(r, k, "GeneratorFunction")),
    (d.isGeneratorFunction = function (Xa) {
      var Ya = "function" == typeof Xa && Xa.constructor;
      return (
        !!Ya &&
        (Ya === q || "GeneratorFunction" === (Ya.displayName || Ya.name))
      );
    }),
    (d.mark = function (Za) {
      return (
        Object.setPrototypeOf
          ? Object.setPrototypeOf(Za, r)
          : ((Za.__proto__ = r), l(Za, k, "GeneratorFunction")),
        (Za.prototype = Object.create(v)),
        Za
      );
    }),
    (d.awrap = function ($a) {
      return { __await: $a };
    }),
    w(x.prototype),
    l(x.prototype, j, function () {
      return this;
    }),
    (d.AsyncIterator = x),
    (d.async = function (_a, ab, bb, cb, db) {
      void 0 === db && (db = Promise);
      var eb = new x(m(_a, ab, bb, cb), db);
      return d.isGeneratorFunction(ab)
        ? eb
        : eb.next().then(function (fb) {
            return fb.done ? fb.value : eb.next();
          });
    }),
    w(v),
    l(v, k, "Generator"),
    l(v, i, function () {
      return this;
    }),
    l(v, "toString", function () {
      return "[object Generator]";
    }),
    (d.keys = function (gb) {
      var hb = Object(gb),
        ib = [];
      for (var jb in hb) ib.push(jb);
      return (
        ib.reverse(),
        function kb() {
          for (; ib.length; ) {
            var lb = ib.pop();
            if (lb in hb) return ((kb.value = lb), (kb.done = !1), kb);
          }
          return ((kb.done = !0), kb);
        }
      );
    }),
    (d.values = D),
    (C.prototype = {
      constructor: C,
      reset: function mb(nb) {
        if (
          ((this.prev = 0),
          (this.next = 0),
          (this.sent = this._sent = undefined),
          (this.done = !1),
          (this.delegate = null),
          (this.method = "next"),
          (this.arg = undefined),
          this.tryEntries.forEach(B),
          !nb)
        )
          for (var ob in this)
            "t" === ob.charAt(0) &&
              f.call(this, ob) &&
              !isNaN(+ob.slice(1)) &&
              (this[ob] = undefined);
      },
      stop: function pb() {
        this.done = !0;
        var qb = this.tryEntries[0].completion;
        if ("throw" === qb.type) throw qb.arg;
        return this.rval;
      },
      dispatchException: function rb(sb) {
        if (this.done) throw sb;
        var tb = this;
        function ub(Ab, Bb) {
          return (
            (xb.type = "throw"),
            (xb.arg = sb),
            (tb.next = Ab),
            Bb && ((tb.method = "next"), (tb.arg = undefined)),
            !!Bb
          );
        }
        for (var vb = this.tryEntries.length - 1; vb >= 0; --vb) {
          var wb = this.tryEntries[vb],
            xb = wb.completion;
          if ("root" === wb.tryLoc) return ub("end");
          if (wb.tryLoc <= this.prev) {
            var yb = f.call(wb, "catchLoc"),
              zb = f.call(wb, "finallyLoc");
            if (yb && zb) {
              if (this.prev < wb.catchLoc) return ub(wb.catchLoc, !0);
              if (this.prev < wb.finallyLoc) return ub(wb.finallyLoc);
            } else if (yb) {
              if (this.prev < wb.catchLoc) return ub(wb.catchLoc, !0);
            } else {
              if (!zb)
                throw new Error("try statement without catch or finally");
              if (this.prev < wb.finallyLoc) return ub(wb.finallyLoc);
            }
          }
        }
      },
      abrupt: function Cb(Db, Eb) {
        for (var Fb = this.tryEntries.length - 1; Fb >= 0; --Fb) {
          var Gb = this.tryEntries[Fb];
          if (
            Gb.tryLoc <= this.prev &&
            f.call(Gb, "finallyLoc") &&
            this.prev < Gb.finallyLoc
          ) {
            var Hb = Gb;
            break;
          }
        }
        Hb &&
          ("break" === Db || "continue" === Db) &&
          Hb.tryLoc <= Eb &&
          Eb <= Hb.finallyLoc &&
          (Hb = null);
        var Ib = Hb ? Hb.completion : {};
        return (
          (Ib.type = Db),
          (Ib.arg = Eb),
          Hb
            ? ((this.method = "next"), (this.next = Hb.finallyLoc), o)
            : this.complete(Ib)
        );
      },
      complete: function Jb(Kb, Lb) {
        if ("throw" === Kb.type) throw Kb.arg;
        return (
          "break" === Kb.type || "continue" === Kb.type
            ? (this.next = Kb.arg)
            : "return" === Kb.type
              ? ((this.rval = this.arg = Kb.arg),
                (this.method = "return"),
                (this.next = "end"))
              : "normal" === Kb.type && Lb && (this.next = Lb),
          o
        );
      },
      finish: function Mb(Nb) {
        for (var Ob = this.tryEntries.length - 1; Ob >= 0; --Ob) {
          var Pb = this.tryEntries[Ob];
          if (Pb.finallyLoc === Nb)
            return (this.complete(Pb.completion, Pb.afterLoc), B(Pb), o);
        }
      },
      catch: function Qb(Rb) {
        for (var Sb = this.tryEntries.length - 1; Sb >= 0; --Sb) {
          var Tb = this.tryEntries[Sb];
          if (Tb.tryLoc === Rb) {
            var Ub = Tb.completion;
            if ("throw" === Ub.type) {
              var Vb = Ub.arg;
              B(Tb);
            }
            return Vb;
          }
        }
        throw new Error("illegal catch attempt");
      },
      delegateYield: function Wb(Xb, Yb, Zb) {
        return (
          (this.delegate = { iterator: D(Xb), resultName: Yb, nextLoc: Zb }),
          "next" === this.method && (this.arg = undefined),
          o
        );
      },
    }),
    d
  );
}
function asyncGeneratorStep($b, _b, ac, bc, cc, dc, ec) {
  try {
    var fc = $b[dc](ec);
    var gc = fc.value;
  } catch (hc) {
    ac(hc);
    return;
  }
  if (fc.done) {
    _b(gc);
  } else {
    Promise.resolve(gc).then(bc, cc);
  }
}
function _asyncToGenerator(ic) {
  return function () {
    var jc = this,
      kc = arguments;
    return new Promise(function (lc, mc) {
      var nc = ic.apply(jc, kc);
      function oc(qc) {
        asyncGeneratorStep(nc, lc, mc, oc, pc, "next", qc);
      }
      function pc(rc) {
        asyncGeneratorStep(nc, lc, mc, oc, pc, "throw", rc);
      }
      oc(undefined);
    });
  };
}
function _classCallCheck(sc, tc) {
  if (!(sc instanceof tc)) {
    throw new TypeError("Cannot call a class as a function");
  }
}
function _defineProperties(uc, vc) {
  for (var wc = 0; wc < vc.length; wc++) {
    var xc = vc[wc];
    xc.enumerable = xc.enumerable || false;
    xc.configurable = true;
    if ("value" in xc) xc.writable = true;
    Object.defineProperty(uc, _toPropertyKey(xc.key), xc);
  }
}
function _createClass(yc, zc, Ac) {
  if (zc) _defineProperties(yc.prototype, zc);
  if (Ac) _defineProperties(yc, Ac);
  Object.defineProperty(yc, "prototype", { writable: false });
  return yc;
}
function _toPropertyKey(Bc) {
  var Cc = _toPrimitive(Bc, "string");
  return _typeof(Cc) === "symbol" ? Cc : String(Cc);
}
function _toPrimitive(Dc, Ec) {
  if (_typeof(Dc) !== "object" || Dc === null) return Dc;
  var Fc = Dc[Symbol.toPrimitive];
  if (Fc !== undefined) {
    var Gc = Fc.call(Dc, Ec || "default");
    if (_typeof(Gc) !== "object") return Gc;
    throw new TypeError("@@toPrimitive must return a primitive value.");
  }
  return (Ec === "string" ? String : Number)(Dc);
}
(function (Hc, Ic, Jc, Kc) {
  var Lc = {
    observer: true,
    observeParents: false,
    speed: 300,
    loop: true,
    slidesPerView: 1,
    navigation: {
      nextEl: ".js-slider-btn_next",
      prevEl: ".js-slider-btn_prev",
      disabledClass: "js-slider-btn_disabled",
    },
    pagination: {
      el: ".js-slider-pagination",
      type: "bullets",
      clickable: true,
    },
  };
  var Mc = (function () {
    function Nc(Oc, Pc) {
      var Qc =
        arguments.length > 2 && arguments[2] !== Kc ? arguments[2] : null;
      _classCallCheck(this, Nc);
      this.element = Oc;
      this.settings = null;
      this.additionalSettings = Pc;
      this.callback = Qc;
      this.forceCreate = false;
    }
    _createClass(
      Nc,
      [
        {
          key: "create",
          value: (function () {
            var Rc = _asyncToGenerator(
              _regeneratorRuntime().mark(function Tc() {
                return _regeneratorRuntime().wrap(
                  function Uc(Vc) {
                    while (1)
                      switch ((Vc.prev = Vc.next)) {
                        case 0:
                          if (
                            this.callback &&
                            typeof this.callback === "function"
                          ) {
                            this.callback();
                          }
                          if (!this.element.swiper) {
                            Vc.next = 3;
                            break;
                          }
                          return Vc.abrupt("return");
                        case 3:
                          if (this.element.dataset.forceCreate) {
                            this.forceCreate = true;
                          }
                          this.settings = Nc.mergeSettings(
                            this.element.dataset.options,
                            this.additionalSettings,
                          );
                          this._setNavigationButtons();
                          this._setPagination();
                          this._contextListener =
                            this._resizeListener.bind(this);
                          Ic.addEventListener("resize", this._contextListener);
                          if (
                            typeof getFormConfig === "function" &&
                            getFormConfig("pictureLazyLoad", false)
                          ) {
                            Jc.dispatchEvent(new Event("picture-lazy:reload"));
                          }
                          if (!this.checkNeedSlider()) {
                            Vc.next = 14;
                            break;
                          }
                          Vc.next = 13;
                          return this._initializeSwiper();
                        case 13:
                          return Vc.abrupt("return");
                        case 14:
                        case "end":
                          return Vc.stop();
                      }
                  },
                  Tc,
                  this,
                );
              }),
            );
            function Sc() {
              return Rc.apply(this, arguments);
            }
            return Sc;
          })(),
        },
        {
          key: "destroy",
          value: function Wc(Xc) {
            if (!this.element.swiper) {
              return;
            }
            this.element.swiper.destroy(true, true);
            if (this._onWheelHandler) {
              Ic.removeEventListener("wheel", this._onWheelHandler);
              this._onWheelHandler = null;
            }
            if (!Xc) {
              this.settings = null;
              Ic.removeEventListener("resize", this._contextListener);
            }
          },
        },
        {
          key: "_initializeSwiper",
          value: function Yc() {
            var Zc = this;
            return new Promise(function ($c, _c) {
              if (Zc.element.swiper) {
                $c();
                return;
              }
              Nc.init()
                .then(function () {
                  new Swiper(Zc.element, Zc.settings);
                  if (
                    typeof getFormConfig === "function" &&
                    getFormConfig("pictureLazyLoad", false)
                  ) {
                    Jc.dispatchEvent(new Event("picture-lazy:reload"));
                  }
                  Zc._enableShiftScroll();
                  $c();
                })
                ["catch"](function (ad) {
                  console.log(
                    "something goes wrong with create slider: ".concat(ad),
                  );
                  _c();
                });
            });
          },
        },
        {
          key: "_setNavigationButtons",
          value: function bd() {
            var cd = this.settings.navigation,
              dd = cd.prevEl,
              ed = cd.nextEl;
            if (dd && ed && typeof dd === "string" && typeof ed === "string") {
              var fd = this.element.querySelector(dd)
                ? this.element.querySelector(dd)
                : this.element.parentNode.querySelector(dd);
              var gd = this.element.querySelector(ed)
                ? this.element.querySelector(ed)
                : this.element.parentNode.querySelector(ed);
              this.settings.navigation.prevEl = fd;
              this.settings.navigation.nextEl = gd;
            }
          },
        },
        {
          key: "_setPagination",
          value: function hd() {
            var id = this.settings.pagination.el;
            if (id && typeof id === "string") {
              var jd;
              if (id.split(",").length === 1) {
                jd = this.element.querySelector(id)
                  ? this.element.querySelector(id)
                  : this.element.parentNode.querySelector(id);
              } else {
                jd = this.settings.pagination.el;
              }
              this.settings.pagination.el = jd;
            }
          },
        },
        {
          key: "_resizeListener",
          value: function kd() {
            if (this.checkNeedSlider()) {
              this._initializeSwiper();
            } else {
              this.destroy(true);
            }
          },
        },
        {
          key: "_enableShiftScroll",
          value: function ld() {
            var md,
              nd = this;
            var od =
              arguments.length > 0 && arguments[0] !== Kc ? arguments[0] : 50;
            var pd =
              (md = this.element) === null || md === void 0
                ? void 0
                : md.swiper;
            if (!pd) return;
            var qd = this.settings.enableShiftScroll;
            if (!qd) return;
            var rd = 0;
            var sd = false;
            var td = function vd() {
              if (!pd) return;
              var wd = pd.maxTranslate();
              var xd = pd.minTranslate();
              var yd = pd.translate - rd;
              if (yd > xd) yd = xd;
              if (yd < wd) yd = wd;
              pd.setTranslate(yd);
              pd.updateProgress();
              rd = 0;
              sd = false;
            };
            var ud = function zd() {
              if (rd >= od) {
                pd.slideNext();
                rd = 0;
              } else if (rd <= -od) {
                pd.slidePrev();
                rd = 0;
              }
              sd = false;
            };
            this._onWheelHandler = function (Ad) {
              if (!nd.element.matches(":hover")) return;
              var Bd = pd.params.loop;
              var Cd = !Bd && pd.isBeginning;
              var Dd = !Bd && pd.isEnd;
              var Ed = pd.params.direction === "vertical";
              var Fd = Ed || Ad.shiftKey;
              var Gd =
                Math.abs(Ad.deltaX) > Math.abs(Ad.deltaY)
                  ? Ad.deltaX
                  : Ad.deltaY;
              var Hd = Fd ? Ad.deltaY : Gd;
              if (!Fd && Math.abs(Ad.deltaY) > Math.abs(Ad.deltaX)) return;
              var Id = Hd > 0;
              var Jd = Hd < 0;
              if ((Id && Dd) || (Jd && Cd)) return;
              Ad.preventDefault();
              if (qd === "smooth") {
                rd += Hd * 0.7;
                if (!sd) {
                  requestAnimationFrame(td);
                  sd = true;
                }
              } else if (qd === "step") {
                rd += Hd;
                if (!sd) {
                  requestAnimationFrame(ud);
                  sd = true;
                }
              }
            };
            Ic.addEventListener("wheel", this._onWheelHandler, {
              passive: false,
            });
          },
        },
        {
          key: "checkNeedSlider",
          value: function Kd() {
            if (this.forceCreate) {
              return true;
            }
            var Ld = Ic.outerWidth;
            var Md = this.settings.breakpoints;
            var Nd = this.settings.slideClass
              ? this.settings.slideClass
              : "swiper-slide";
            var Od = this.element.querySelectorAll(
              ".".concat(Nd, ":not(.swiper-slide-duplicate)"),
            ).length;
            var Pd = this.settings.slidesPerView;
            if (Md) {
              var Qd = [];
              for (var Rd in Md) {
                if (Md[Rd].hasOwnProperty("slidesPerView") && +Rd <= Ld) {
                  Qd.push({ width: +Rd, slidesPerView: Md[Rd].slidesPerView });
                }
              }
              if (!Qd.length && ((Pd && Pd < Od) || Pd === "auto")) {
                return true;
              }
              Qd.sort(function (Td, Ud) {
                return Td.width - Ud.width;
              });
              var Sd = Qd.length ? Qd[Qd.length - 1] : null;
              if (
                Sd &&
                (Sd.slidesPerView < Od || Sd.slidesPerView == "auto") &&
                Sd.slidesPerView !== 0
              ) {
                return true;
              } else {
                return false;
              }
            } else if ((Pd && Pd < Od) || Pd === "auto") {
              return true;
            } else {
              return false;
            }
          },
        },
      ],
      [
        {
          key: "init",
          value: function Vd() {
            return new Promise(function (Wd, Xd) {
              if (typeof Swiper !== "function") {
                var Yd =
                  typeof getStoreConfig === "function" &&
                  getStoreConfig("swiperScriptLink", false)
                    ? getStoreConfig("swiperScriptLink")
                    : "//cdn.aristosgroup.ru/libs/swiper/swiper-5.3.8.min.js";
                var Zd =
                  typeof getStoreConfig === "function" &&
                  getStoreConfig("swiperStyleLink", false)
                    ? getStoreConfig("swiperStyleLink")
                    : "//cdn.aristosgroup.ru/libs/swiper/swiper-5.3.8.min.css";
                loadFiles([Yd, Zd], function () {
                  Nc.checkAvailabilitySwiper()
                    .then(function () {
                      Wd();
                    })
                    ["catch"](function () {
                      Xd();
                    });
                });
              } else {
                Wd();
              }
            })["catch"](function ($d) {
              console.error("Ошибка инициализации свайпера: ", $d);
            });
          },
        },
        {
          key: "mergeSettings",
          value: function _d(ae, be) {
            var ce = {};
            if (ae) {
              try {
                ce = JSON.parse(ae);
              } catch (ee) {
                console.warn("json parse error: " + ee);
              }
            }
            var de = JSON.parse(JSON.stringify(Lc));
            return this.mergeDeep(de, this.mergeDeep(be, ce));
          },
        },
        {
          key: "checkAvailabilitySwiper",
          value: function fe() {
            return new Promise(function (ge, he) {
              var ie = 1;
              var je = setInterval(function () {
                ie++;
                if (typeof Swiper === "function") {
                  clearInterval(je);
                  ge();
                }
                if (ie > 100) {
                  clearInterval(je);
                  he();
                }
              }, 200);
            })["catch"](function (ke) {
              console.error("Ошибка проверки доступности свайпера: ", ke);
            });
          },
        },
        {
          key: "mergeDeep",
          value: function le(me, ne) {
            var oe = this;
            var pe = function qe(re) {
              return re && _typeof(re) === "object";
            };
            if (!pe(me) || !pe(ne)) {
              return ne;
            }
            Object.keys(ne).forEach(function (se) {
              var te = me[se];
              var ue = ne[se];
              if (Array.isArray(te) && Array.isArray(ue)) {
                me[se] = te.concat(ue);
              } else if (pe(te) && pe(ue)) {
                me[se] = oe.mergeDeep(Object.assign({}, te), ue);
              } else {
                me[se] = ue;
              }
            });
            return me;
          },
        },
      ],
    );
    return Nc;
  })();
  Ic.initUniversalSlider = function (ve) {
    var we = arguments.length > 1 && arguments[1] !== Kc ? arguments[1] : {};
    var xe = arguments.length > 2 && arguments[2] !== Kc ? arguments[2] : null;
    if (!ve) {
      throw new Error("element is required for init slider");
    }
    if (ve.swiper) {
      console.log("slider was already inited");
      return;
    }
    if (we && _typeof(we) !== "object") {
      console.log("the settings must be an object");
      return;
    }
    if (ve.classList.contains("js-slider-created")) {
      return false;
    } else {
      ve.classList.add("js-slider-created");
      return new Mc(ve, we, xe);
    }
  };
})(jQuery, window, document);

(function (a, b) {
  function c() {
    var d = new CustomEvent("universalSlider::create");
    b.querySelectorAll(".ellipse__content__utp").forEach(function (o) {
      o.addEventListener("mouseenter", function () {
        o.classList.add("active");
        o.querySelectorAll(".ellipse__content__utp__icon").forEach(
          function (p) {
            p.classList.toggle("utp-icon_visible");
          },
        );
        o.querySelector(".ellipse__content__utp__title").classList.add(
          "active",
        );
      });
      o.addEventListener("mouseleave", function () {
        o.classList.remove("active");
        o.querySelectorAll(".ellipse__content__utp__icon").forEach(
          function (q) {
            q.classList.toggle("utp-icon_visible");
          },
        );
        o.querySelector(".ellipse__content__utp__title").classList.remove(
          "active",
        );
      });
    });
    var e = b.querySelector(".b2b-advantages__description__text");
    function f() {
      if (a.innerWidth <= 480) {
        e.innerHTML =
          '\n            <a class="b2b-advantages__description__text_link" href="#">Уникальные преимущества</a>\n            для В2В клиентов\n        ';
      } else {
        e.innerHTML =
          '\n            Для В2В клиентов мы предлагаем свои\n            <a class="b2b-advantages__description__text_link" href="#">уникальные преимущества</a>\n        ';
      }
    }
    if (e) {
      f();
      a.addEventListener("resize", f);
    }
    if (typeof a.initUniversalSlider === "function") {
      var g = b.getElementById("promotions-block-slider");
      if (g) {
        var h = {
          spaceBetween: 16,
          loop: false,
          a11y: false,
          pagination: {
            el: ".promotions-tiles__pagination-container__bullets",
            clickable: true,
          },
          breakpoints: {
            0: { slidesPerView: 1 },
            481: { slidesPerView: "auto" },
            1024: { slidesPerView: 0 },
          },
        };
        var i = {
          spaceBetween: 16,
          loop: false,
          pagination: {
            el: ".promotions-tiles__pagination-container__bullets",
            clickable: true,
          },
          navigation: {
            nextEl: ".promotions-tiles__pagination-next-btn",
            prevEl: ".promotions-tiles__pagination-prev-btn",
          },
          breakpoints: {
            0: { slidesPerView: 1 },
            481: { slidesPerView: "auto" },
            1024: {
              slidesPerView: 2,
              spaceBetween: 27,
              navigation: {
                nextEl: ".promotions-tiles__pagination-next-btn",
                prevEl: ".promotions-tiles__pagination-prev-btn",
              },
            },
          },
        };
        var j = g.classList.contains("b2b") ? i : h;
        var k = a.initUniversalSlider(g, j);
        k.create().then(function () {
          g.dispatchEvent(d);
        });
      }
      var l = b.getElementById("collections-block-slider");
      if (l) {
        var m = a.initUniversalSlider(l, {
          spaceBetween: 24,
          loop: true,
          pagination: {
            el: ".collections-tiles__pagination-container__bullets",
            clickable: true,
          },
          navigation: {
            nextEl: ".collections-tiles__pagination-next-btn",
            prevEl: ".collections-tiles__pagination-prev-btn",
          },
          nested: true,
          breakpoints: {
            0: {
              slidesPerView: 1,
              autoplay: { delay: 6e3, disableOnInteraction: false },
            },
            480: {
              slidesPerView: 2,
              loop: false,
              navigation: {
                nextEl: ".collections-tiles__pagination-next-btn",
                prevEl: ".collections-tiles__pagination-prev-btn",
              },
            },
            1024: {
              slidesPerView: 3,
              loop: false,
              navigation: {
                nextEl: ".collections-tiles__pagination-next-btn",
                prevEl: ".collections-tiles__pagination-prev-btn",
              },
            },
          },
        });
        m.create().then(function () {
          l.dispatchEvent(d);
        });
        var n = l.querySelectorAll(".collections-tile__slides");
        n.forEach(function (r) {
          var s = a.initUniversalSlider(r, {
            slidesPerView: 1,
            spaceBetween: 0,
            loop: true,
            autoplay: { delay: 6e3, disableOnInteraction: false },
            effect: "fade",
            fadeEffect: { crossFade: true },
            speed: 1e3,
            nested: true,
            allowTouchMove: false,
          });
          s.create().then(function () {
            r.dispatchEvent(d);
          });
        });
      }
    }
  }
  b.addEventListener("DOMContentLoaded", c);
})(window, document);

(function (a, b, c) {
  b.addEventListener("DOMContentLoaded", function () {
    if (typeof a.aristosSkeletion === "function") {
      var d = new a.aristosSkeletion();
      d.init().start();
    } else {
      console.warn(
        "[Init Skeleton] Инициализация Skeleton приостановлена. Нет объекта AristosSkeleton",
      );
    }
  });
})(window, document);

var customEvents = (function () {
  var a = jQuery({});
  return { on: b, trigger: c };
  function b() {
    a.on.apply(a, arguments);
  }
  function c() {
    a.trigger.apply(a, arguments);
  }
})();
var configTagMan = {
  selector: {
    widget: {
      products: {
        parent: ".products-grid",
        sku: ".sku,.product-sku",
        shown: ".products-grid li,.listing-item",
        pill: false,
      },
    },
  },
};
var gtmCheckoutStepsConfig = {
  1: {
    trigger: { step: "step_first" },
    selector: {
      "shipping-select-delivery": "courier",
      "shipping-select-pickup": "pickup",
    },
  },
  2: {
    trigger: { step: "step_second" },
    selector: {
      courier: {
        aristos_du: "office",
        citycourier_base: "our_courier",
        deliveryems_deliveryems: "transport_company",
      },
      pickup: { 1: "office", pickup_select: "pickup_point" },
    },
  },
  3: {
    trigger: { step: "step_third" },
    selector: {
      cloudpayments: "card_online",
      cashondelivery: "cash",
      cardoffline: "card_courier",
      sbrf: "bank_receipt",
    },
  },
};
var selectorSliderRevolution = ".slider-banner-container .slider-philips";
var selectorSliderBX = ".mb_slider_container";
customEvents.on("config-tag-man", function (d, e) {
  if (e.hasOwnProperty("addConfig")) {
    jQuery.extend(true, configTagMan, e.addConfig);
  }
});
customEvents.on("slider-config-tag-man", function (f, g) {
  if (g.hasOwnProperty("revolution")) {
    selectorSliderRevolution = g.revolution;
  }
  if (g.hasOwnProperty("bx")) {
    selectorSliderBX = g.bx;
  }
});
jQuery(document).on("ready ", function () {
  document.cookie = "browser_lang=" + navigator.language.toLowerCase();
  document.cookie =
    "screen_res=" +
    (window.screen ? window.screen.width : 0) +
    "x" +
    (window.screen ? window.screen.height : 0);
  window.sliderRevolutionHome = jQuery(selectorSliderRevolution);
  function h(i, j, k) {
    var l = gtmCheckoutStepsConfig[i];
    var m = l.trigger.step;
    var n = false;
    if (typeof k !== "undefined") {
      n = l.selector[j][k];
    } else {
      n = l.selector[j];
    }
    var o = typeof n !== "undefined" ? n : k;
    if (typeof o === "undefined") {
      o = j;
    }
    customEvents.trigger(m, { step: i, option: o });
  }
  jQuery(window).on("load", function () {
    var p = jQuery("#delivery-fieldset .legend");
    var q = p.find(".active").attr("id");
    if (typeof q !== "undefined") {
      h(1, q);
    }
  });
  jQuery("#shipping-select-delivery").click(function () {
    h(1, "shipping-select-delivery");
  });
  jQuery("#shipping-select-pickup").click(function () {
    h(1, "shipping-select-pickup");
  });
  jQuery("#shipping-methods-row").click(function () {
    var r = jQuery(this).children("ul").find(".active").data("id");
    h(2, "courier", r);
  });
  jQuery(".pickup-methods").click(function () {
    var s = jQuery(this).find(".active").data("id");
    h(2, "pickup", s);
  });
  jQuery("#pickup_select").click(function () {
    var t = jQuery(this).attr("id");
    h(2, "pickup", t);
  });
  jQuery("#payment-methods").click(function () {
    var u = jQuery(this).children("li.active").data("id");
    h(3, u);
  });
});
