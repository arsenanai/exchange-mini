/// <reference types="node" />

import js from '@eslint/js';
import eslintConfigPrettier from 'eslint-config-prettier';
import pluginVue from 'eslint-plugin-vue';
import tseslint from 'typescript-eslint';
import globals from 'globals';
import type { TSESLint } from '@typescript-eslint/utils';

const config: TSESLint.FlatConfig.ConfigArray = [
  js.configs.recommended,
  ...tseslint.configs.recommended,
  ...pluginVue.configs['flat/recommended'],
  eslintConfigPrettier,
  {
    ignores: [
      'node_modules/',
      'vendor/',
      'public/',
      'bootstrap/cache/',
      'storage/',
      'resources/js/coverage/',
      'vite.config.js',
      'vite.config.testing.js',
      'vitest.config.ts',
      'tailwind.config.ts',
      'postcss.config.cjs',
      '*.json',
      'playwright-report/',
      'playwright.config.ts',
    ],
  },
  {
    files: ['**/*.vue'],
    languageOptions: {
      parserOptions: {
        // This tells @typescript-eslint to use your tsconfig.json for type information
        // Adjust the path if your main tsconfig.json is not at the root
        project: './tsconfig.json',
        extraFileExtensions: ['.vue'],
        parser: tseslint.parser,
      },
    },
  },
  {
     // Apply browser globals only to the source code files
    files: ['resources/js/**/*.ts', 'resources/js/**/*.vue'],
    languageOptions: {
      parserOptions: {
        // Also apply project setting for .ts and .vue files
        project: './tsconfig.json',
        extraFileExtensions: ['.vue'],
      },
      globals: {
        ...globals.browser,
      },
    },
  },
  {
    rules: {
      'no-console': process.env.NODE_ENV === 'production' ? 'warn' : 'off',
      'no-debugger': process.env.NODE_ENV === 'production' ? 'warn' : 'off',
      'vue/multi-word-component-names': 'off',
    },
  },
];

export default config;
