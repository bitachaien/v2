import { defineConfig, loadEnv } from 'vite'
import vue from '@vitejs/plugin-vue'
import { resolve } from 'path'

export default defineConfig(({ mode }) => {
  const env = loadEnv(mode, process.cwd(), '')
  const isProduction = mode === 'production'

  return {
  plugins: [
    vue({
      template: {
        compilerOptions: {
          isCustomElement: (tag) => ['marquee'].includes(tag)
        }
      }
    })
  ],
  resolve: {
    alias: {
      '@': resolve(__dirname, 'src')
    }
  },
  base: '/',
  server: { 
    host: '0.0.0.0',  
    open: true, 
    port: 5173,
    proxy: {
      '/api': {
        target: env.VITE_PROXY_TARGET || 'http://localhost:3000',
        changeOrigin: true,
      },
      '/app/admin/upload': {
        target: env.VITE_PROXY_TARGET || 'http://localhost:3000',
        changeOrigin: true,
      },
      '/uploads': {
        target: env.VITE_PROXY_TARGET || 'http://localhost:3000',
        changeOrigin: true,
      }
    }
  },
  // 生产构建优化
  build: {
    // 移除 console 和 debugger
    minify: 'terser',
    terserOptions: {
      compress: {
        drop_console: isProduction,
        drop_debugger: isProduction,
      },
    },
    sourcemap: !isProduction,
    // 分包策略
    rollupOptions: {
      output: {
        manualChunks: {
          'vendor': ['vue', 'vue-router', 'pinia'],
          'vant': ['vant'],
          'echarts': ['echarts'],
        }
      }
    },
    // 块大小警告阈值
    chunkSizeWarningLimit: 1000,
  },
  css: {
    preprocessorOptions: {
      less: {
        modifyVars: {
          hack: `true; @import "${resolve(__dirname, 'src/assets/theme.less')}";`,
        },
        javascriptEnabled: true,
      },
    },
  },
}})
