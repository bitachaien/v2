
export const optimizationConfig = {
  // ==================== 构建优化 ====================
  build: {
    // 目标浏览器
    target: 'es2015',
    
    // 压缩配置
    minify: 'terser',
    terserOptions: {
      compress: {
        // 生产环境移除console
        drop_console: true,
        drop_debugger: true,
        pure_funcs: ['console.log', 'console.info']
      }
    },
    
    // 代码分割
    rollupOptions: {
      output: {
        // 手动分包
        manualChunks: {
          // 核心
          'vue-vendor': ['vue', 'vue-router', 'pinia'],
          
          // 组件库
          'vant': ['vant'],
          
          // 图表库
          'echarts': ['echarts'],
          
          // 工具库
          'utils': ['axios', '@vueuse/core']
        },
        
        // 文件命名
        chunkFileNames: 'js/[name]-[hash].js',
        entryFileNames: 'js/[name]-[hash].js',
        assetFileNames: (assetInfo) => {
          if (assetInfo.name.endsWith('.css')) {
            return 'css/[name]-[hash][extname]'
          }
          if (/\.(png|jpe?g|gif|svg|webp)$/.test(assetInfo.name)) {
            return 'images/[name]-[hash][extname]'
          }
          return 'assets/[name]-[hash][extname]'
        }
      }
    },
    
    // Chunk大小警告限制
    chunkSizeWarningLimit: 1000,
    
    // 生成sourcemap）
    sourcemap: false,
    
    // CSS代码分割
    cssCodeSplit: true,
    
    // 报告压缩信息
    reportCompressedSize: true
  },

  // ==================== ESBuild优化 ====================
  esbuild: {
    // 生产环境移除console和debugger
    drop: process.env.NODE_ENV === 'production' ? ['console', 'debugger'] : [],
    
    // 压缩标识符
    minifyIdentifiers: true,
    
    // 压缩语法
    minifySyntax: true,
    
    // 压缩空白
    minifyWhitespace: true
  },

  // ==================== 优化依赖 ====================
  optimizeDeps: {
    include: [
      'vue',
      'vue-router',
      'pinia',
      'vant',
      'axios'
    ],
    exclude: [
      // 排除不需要预构建的依赖
    ]
  },

  // ==================== 服务器配置 ====================
  server: {
    // 启用压缩
    compress: true,
    
    // 预热常用文件
    warmup: {
      clientFiles: [
        './src/main.js',
        './src/App.vue',
        './src/router/index.js'
      ]
    }
  }
}

/**
 * 使用示例：
 * 
 * // vite.config.js
 * import { defineConfig } from 'vite'
 * import vue from '@vitejs/plugin-vue'
 * import { optimizationConfig } from './vite.config.optimization.js'
 * 
 * export default defineConfig({
 *   plugins: [vue()],
 *   ...optimizationConfig,
 *   // 你的其他配置...
 * })
 */
