module.exports = {
  // 一行最多多少字符
  printWidth: 100,
  // 使用2个空格缩进
  tabWidth: 2,
  // 使用空格缩进
  useTabs: false,
  // 不需要分号
  semi: false,
  // 使用单引号
  singleQuote: true,
  // 对象的key仅在必要时使用引号
  quoteProps: 'as-needed',
  // JSX使用单引号
  jsxSingleQuote: false,
  // 不需要尾随逗号
  trailingComma: 'none',
  // 大括号内的首尾需要空格
  bracketSpacing: true,
  // 箭头函数，只有一个参数的时候，也需要括号
  arrowParens: 'always',
  // 每个文件格式化的范围是文件的全部内容
  rangeStart: 0,
  rangeEnd: Infinity,
  // 不需要写文件开头的 @prettier
  requirePragma: false,
  // 不需要自动在文件开头插入 @prettier
  insertPragma: false,
  // 使用默认的折行标准
  proseWrap: 'preserve',
  // 根据显示样式决定 html 要不要折行
  htmlWhitespaceSensitivity: 'css',
  // Vue文件script和style标签中是否缩进
  vueIndentScriptAndStyle: false,
  // 换行符使用 lf
  endOfLine: 'lf',
  // 格式化内嵌代码
  embeddedLanguageFormatting: 'auto'
}
