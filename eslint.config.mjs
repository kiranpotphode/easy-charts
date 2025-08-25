import globals from "globals";
import pluginJs from "@eslint/js";

/** @type {import('eslint').Linter.Config[]} */
export default [
  {languageOptions: { globals: globals.browser }},
  pluginJs.configs.recommended,
	{
		"rules": {
			"camelcase": [1],
			"space-in-parens": [1, "always"],
			"no-trailing-spaces": [1],
			"spaced-comment": [0],
			"padded-blocks": [0],
			"prefer-template": [0],
			"max-len": [0],
			"no-else-return": [0],
			"func-names": [0],
			"object-shorthand": [0],
			"indent": ["error", "tab"],
			"space-before-function-paren": 0,
			"no-tabs": 0,
			"prefer-destructuring": 0,
			"no-undef": 0,
			"no-param-reassign": 0,
			"no-unused-vars": 1,
		}
	}
];