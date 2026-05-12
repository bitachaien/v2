import { defineStore } from 'pinia';

export const useK3BetStore = defineStore('k3Bet', {
  state: () => ({
    selections: [], 
    betMultiplier: 1,
    denomination: 2, 
  }),

  getters: {
    totalBetCount() {
      return this.selections.reduce((acc, selection) => acc + selection.items.length, 0);
    },
    totalAmount() {

      return this.totalBets * this.betMultiplier * this.denomination;
    },
    totalBets(state) {

      return state.selections.reduce((total, selection) => total + selection.items.length, 0);
    },
    totalCost(state) {
      return this.totalBets * state.betMultiplier * state.denomination;
    },
  },

  actions: {
    toggleSelection(item, playId) {
      const existing = this.selections.find(s => s.playId === playId);

      if (existing) {

        const itemIndex = existing.items.findIndex(i => i.name === item.name);
        if (itemIndex > -1) {
          existing.items.splice(itemIndex, 1);
          if (existing.items.length === 0) {
            this.clearBet(playId);
          }
        } else {
          existing.items.push(item);
        }
      } else {
        this.selections.push({ playId: playId, items: [item] });
      }
    },

    clearBet(playId) {
      const index = this.selections.findIndex(s => s.playId === playId);
      if (index > -1) {
        this.selections.splice(index, 1);
      }
    },

    clearAllBets() {
      this.selections = [];
    },

    setMultiplier(multiplier) {
      this.betMultiplier = multiplier;
    },
    setDenomination(value) {
      this.denomination = value;
    },
  },
});
