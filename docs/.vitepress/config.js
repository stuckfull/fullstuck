import { defineConfig } from 'vitepress'

export default defineConfig({
  title: 'FullStuck.php',
  description: 'Path-Based Colocation, 1-File PHP Micro-Framework',
  base: '/',
  head: [['link', { rel: 'icon', type: 'image/svg+xml', href: '/logo.svg' }]],
  themeConfig: {
    logo: '/logo.svg',
    siteTitle: 'FullStuck.php',
    nav: [
      { text: 'Home', link: '/' },
      { 
        text: 'Versi',
        items: [
          { text: 'v0.4.0 (Terbaru)', link: '/v0.4/' },
          { text: 'v0.3.0 (Legacy)', link: '/v0.3/' },
          { text: 'v0.2.0 (Legacy)', link: '/v0.2/' }
        ]
      },
      { text: 'Panduan AI', link: '/ai-setup' },
      { text: 'Changelog', link: 'https://github.com/stuckfull/fullstuck/blob/main/CHANGELOG.md' }
    ],
    sidebar: {
      '/v0.4/': [
        {
          text: '🚀 The Basics',
          items: [
            { text: '📦 Instalasi & Quick Start', link: '/v0.4/01-getting-started' },
            { text: '🏗️ Struktur Folder & Routing', link: '/v0.4/02-routing' },
            { text: '⚙️ Konfigurasi', link: '/v0.4/09-config' },
          ]
        },
        {
          text: '🎨 View & Frontend',
          items: [
            { text: '🎨 Syntax Templating', link: '/v0.4/03-templates' },
            { text: '🧩 Components', link: '/v0.4/04-components' },
            { text: '🔌 FST-Agent (SPA Engine)', link: '/v0.4/05-fst-agent' },
          ]
        },
        {
          text: '⚙️ Backend & API',
          items: [
            { text: '🛠️ Action & Headless API', link: '/v0.4/06-action-api' },
            { text: '🗄️ Database', link: '/v0.4/08-database' },
            { text: '🔒 Keamanan & Session', link: '/v0.4/07-security' },
            { text: '🧾 Logging & Error Handling', link: '/v0.4/10-logging' },
          ]
        },
        {
          text: '📚 Referensi & Lanjutan',
          items: [
            { text: '🌶️ Cookbook & Tips Lanjutan', link: '/v0.4/12-cookbook' },
            { text: '⚛️ Monolith SPA', link: '/v0.4/13-monolith-spa' },
            { text: '📚 API Reference', link: '/v0.4/11-api-reference' },
            { text: '📖 Versi Lengkap', link: '/v0.4/FULL' }
          ]
        }
      ],
      '/v0.3/': [
        {
          text: 'Mulai Cepat',
          items: [
            { text: 'Pengenalan & Instalasi', link: '/v0.3/01-getting-started' },
            { text: 'Versi Lengkap (All-in-One)', link: '/v0.3/FULL' }
          ]
        },
        {
          text: 'Core Features',
          items: [
            { text: 'Routing & Middleware', link: '/v0.3/02-routing' },
            { text: 'Database & Query', link: '/v0.3/03-database' },
            { text: 'Security & Validation', link: '/v0.3/04-security' },
            { text: 'DOM Templating', link: '/v0.3/05-templates' },
            { text: 'Advanced Cookbook', link: '/v0.3/08-advanced-cookbook' }
          ]
        },
        {
          text: 'Front-End',
          items: [
            { text: 'FST Agent (Hybrid SPA)', link: '/v0.3/06-fst-agent' }
          ]
        },
        {
          text: 'Observability',
          items: [
            { text: 'Logging & Error Handling', link: '/v0.3/07-logging' }
          ]
        },
        {
          text: 'Referensi',
          items: [
            { text: 'API Reference', link: '/v0.3/09-api-reference' }
          ]
        }
      ],
      '/': [
        {
          text: '🚀 Versi Terbaru',
          items: [
            { text: 'Dokumentasi v0.4.0', link: '/v0.4/' }
          ]
        },
        {
          text: 'Konsep & Panduan Lanjut',
          items: [
            { text: 'SOP & Panduan Setup AI', link: '/ai-setup' }
          ]
        },
        {
          text: 'Arsip (Legacy)',
          items: [
            { text: 'Dokumentasi v0.3.0', link: '/v0.3/' },
            { text: 'Dokumentasi v0.2.0', link: '/v0.2/' },
            { text: 'Cheatsheet v0.2.0', link: '/v0.2/cheatsheet' },
            { text: 'Dokumentasi v0.1.0', link: '/v0.1/' }
          ]
        }
      ]
    },
    socialLinks: [
      { icon: 'github', link: 'https://github.com/stuckfull/fullstuck' }
    ],
    footer: {
      message: 'Dibuat dengan ❤️ untuk ekosistem PHP yang lebih sederhana.',
      copyright: 'Copyright © 2026-present Stuckfull'
    }
  }
})
