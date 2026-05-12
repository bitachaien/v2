import { defineStore } from 'pinia';

export const usePk10BetStore = defineStore('pk10Bet', {
  state: () => ({
    selections: [], 
    betMultiplier: 1,
    denomination: 2, 
  }),

  getters: {
    totalBetCount(state) {
      return state.selections.reduce((acc, selection) => acc + selection.items.length, 0);
    },
    totalAmount(state) {
      return state.totalBetCount * state.betMultiplier * state.denomination;
    },
    totalBets(state) {

      return state.totalBetCount;
    },
    totalCost(state) {
        return state.totalAmount;
    }
  },

  actions: {
    toggleSelection(item, playId) {
      let group = this.selections.find(s => s.playId === playId);

      if (!group) {
        group = { playId: playId, items: [] };
        this.selections.push(group);
      }

      const itemIndex = group.items.findIndex(i => i.id === item.id);
      if (itemIndex > -1) {

        group.items.splice(itemIndex, 1);
        if (group.items.length === 0) {
          const groupIndex = this.selections.findIndex(s => s.playId === playId);
          this.selections.splice(groupIndex, 1);
        }
      } else {

        group.items.push(item);
      }
    },

    isSelected(itemId, playId) {
        const group = this.selections.find(s => s.playId === playId);
        return group ? group.items.some(i => i.id === itemId) : false;
    },

    clearAllBets() {
      this.selections = [];
    },

    clearSelection() {
      this.selections = [];
    },

    setMultiplier(multiplier) {
      this.betMultiplier = multiplier;
    },
    setDenomination(value) {
      this.denomination = value;
    },
    
    
    buildBetData() {
      const bets = [];
      
      
      for (const group of this.selections) {
        for (const item of group.items) {
          let playId = '';
          let tzcode = '';
          
          

          const isSinglePlay = (group.playId && group.playId.startsWith('single_qian')) || item.type === 'single';
          
          if (isSinglePlay && item.nums && item.nums.length > 0) {

            const playMatch = group.playId.match(/(qian\d+)/);
            if (playMatch) {
              playId = playMatch[1]; 
              tzcode = item.nums.map(n => n < 10 ? '0'+n : n).join(',');
            }
          } else {

            playId = item.id;
            const parts = item.id.split('_');
            tzcode = parts.length > 1 ? parts[parts.length - 1] : item.id;
          }
          
          if (!playId) {
            continue;
          }
          
          const betAmount = this.betMultiplier * this.denomination;
          
          const betData = {
            playId: playId,
            tzcode: tzcode,
            amount: betAmount,
            multiplier: 1,
            betCount: 1
          };
          bets.push(betData);
        }
      }
      
      return bets;
    },
  },
});
