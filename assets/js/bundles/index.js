/**
 * Bundle Index
 * Central export point for all bundles
 * 
 * @generated 2025-06-19 18:31:10
 * @package MemberpressAiAssistant
 */

export * as CoreBundle from './core-bundle.js';
export * as UiBundle from './ui-bundle.js';
export * as MessagingBundle from './messaging-bundle.js';
export * as MessageHandlersBundle from './message-handlers-bundle.js';

// Bundle loading utilities
export const BundleLoader = {
  coreBundle: () => import('./core-bundle.js'),
  async loadUiBundle() {
    return await import('./ui-bundle.js');
  },
  messagingBundle: () => import('./messaging-bundle.js'),
  async loadMessageHandlersBundle() {
    return await import('./message-handlers-bundle.js');
  },
};
