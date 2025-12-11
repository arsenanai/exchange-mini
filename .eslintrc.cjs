/* eslint-env node */
require('@rushstack/eslint-patch/modern-module-resolution');

module.exports = {
  root: true,
  parser: 'vue-eslint-parser',
  parserOptions: {
    parser: '@typescript-eslint/parser',
    ecmaVersion: 2020,
    sourceType: 'module',
    extraFileExtensions: ['.vue'],
    project: ['./tsconfig.json'],
    tsconfigRootDir: __dirname,
  },
  extends: [
    'plugin:vue/vue3-recommended',
    'eslint:recommended',
    '@vue/eslint-config-typescript',
    '@vue/eslint-config-prettier/skip-formatting',
  ],
  rules: {
    'no-console': process.env.NODE_ENV === 'production' ? 'warn' : 'off',
    'no-debugger': process.env.NODE_ENV === 'production' ? 'warn' : 'off',

    // 👇 disable the multi-word component name rule
    'vue/multi-word-component-names': 'off',
  },
  ignorePatterns: [
    'node_modules/',
    'vendor/',
    'public/',
    'bootstrap/cache/',
    'storage/',
    'resources/js/coverage/',
    'vite.config.js',
    'vitest.config.ts',
    'tailwind.config.ts',
    'postcss.config.cjs',
    '*.json',
  ],
};
