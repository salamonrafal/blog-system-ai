import { qs, qsa } from './shared.js';

export function createSortableTree(root, {
  levelSelector,
  nodeSelector,
  handleSelector,
  nodeIdAttribute,
  onSyncNode,
  onDragStateChange,
  onStatusChange,
  onPersistOrder,
} = {}){
  if(!(root instanceof HTMLElement)){
    return { setup(){} };
  }

  const safeLevelSelector = levelSelector || '[data-sortable-tree-level]';
  const safeNodeSelector = nodeSelector || '[data-sortable-tree-node]';
  const safeHandleSelector = handleSelector || '[data-sortable-tree-handle]';
  const safeNodeIdAttribute = nodeIdAttribute || 'data-item-id';

  const nodeById = new Map(
    qsa(safeNodeSelector, root)
      .map((node)=> [node.getAttribute(safeNodeIdAttribute) || '', node])
      .filter(([id, node])=> id !== '' && node instanceof HTMLElement)
  );

  let draggedNode = null;
  let sourceLevel = null;
  let originalIds = [];
  let armedNode = null;

  const emitStatus = (translationKey = '', type = 'info')=>{
    if(typeof onStatusChange === 'function'){
      onStatusChange({ translationKey, type });
    }
  };

  const setDragActive = (isActive)=>{
    if(typeof onDragStateChange === 'function'){
      onDragStateChange({ isActive });
    }
  };

  const collectLevelNodes = (level)=>{
    if(!(level instanceof HTMLElement)){
      return [];
    }

    return [...level.children].filter((child)=> child instanceof HTMLElement && child.matches(safeNodeSelector));
  };

  const collectOrderedIds = (level)=> collectLevelNodes(level)
    .map((node)=> Number(node.getAttribute(safeNodeIdAttribute) || '0'))
    .filter((id)=> Number.isInteger(id) && id > 0);

  const syncLevel = (level)=>{
    collectLevelNodes(level).forEach((node, index)=>{
      if(typeof onSyncNode === 'function'){
        onSyncNode({ node, index, level });
      }
    });
  };

  const restoreLevelOrder = (level, orderedIds)=>{
    orderedIds.forEach((id)=>{
      const node = nodeById.get(String(id));
      if(node instanceof HTMLElement){
        level.appendChild(node);
      }
    });

    syncLevel(level);
  };

  const clearDragState = ()=>{
    qsa('.is-dragging, .is-drop-target-before, .is-drop-target-after', root).forEach((element)=>{
      element.classList.remove('is-dragging', 'is-drop-target-before', 'is-drop-target-after');
    });
  };

  const disarmNode = (node)=>{
    if(!(node instanceof HTMLElement)){
      return;
    }

    node.draggable = false;
    if(armedNode === node){
      armedNode = null;
    }
  };

  const armNode = (node)=>{
    if(!(node instanceof HTMLElement)){
      return;
    }

    if(armedNode instanceof HTMLElement && armedNode !== node){
      disarmNode(armedNode);
    }

    armedNode = node;
    node.draggable = true;
  };

  const getDropReferenceNode = (level, pointerY)=>{
    const siblings = collectLevelNodes(level).filter((node)=> node !== draggedNode);
    let closestOffset = Number.NEGATIVE_INFINITY;
    let closestNode = null;

    siblings.forEach((node)=>{
      const rect = node.getBoundingClientRect();
      const offset = pointerY - rect.top - (rect.height / 2);

      if(offset < 0 && offset > closestOffset){
        closestOffset = offset;
        closestNode = node;
      }
    });

    return closestNode;
  };

  const persistLevelOrder = async (level, previousOrder)=>{
    if(!(level instanceof HTMLElement) || typeof onPersistOrder !== 'function'){
      return;
    }

    const orderedIds = collectOrderedIds(level);

    try{
      const persisted = await onPersistOrder({
        level,
        orderedIds,
        restoreLevelOrder,
        previousOrder,
        syncLevel,
      });

      if(persisted === false){
        restoreLevelOrder(level, previousOrder);
      }
    }catch(error){
      restoreLevelOrder(level, previousOrder);
      throw error;
    }
  };

  const setup = ()=>{
    qsa(safeLevelSelector, root).forEach((level)=>{
      syncLevel(level);

      level.addEventListener('dragover', (event)=>{
        if(!(draggedNode instanceof HTMLElement) || level !== sourceLevel){
          return;
        }

        event.preventDefault();
        collectLevelNodes(level).forEach((node)=>{
          node.classList.remove('is-drop-target-before', 'is-drop-target-after');
        });

        const referenceNode = getDropReferenceNode(level, event.clientY);
        if(referenceNode === null){
          const lastNode = collectLevelNodes(level).filter((node)=> node !== draggedNode).at(-1);
          if(lastNode instanceof HTMLElement){
            lastNode.classList.add('is-drop-target-after');
          }
          level.appendChild(draggedNode);
        }else{
          referenceNode.classList.add('is-drop-target-before');
          level.insertBefore(draggedNode, referenceNode);
        }
      });

      level.addEventListener('dragleave', (event)=>{
        if(event.target === level){
          collectLevelNodes(level).forEach((node)=>{
            node.classList.remove('is-drop-target-before', 'is-drop-target-after');
          });
        }
      });

      level.addEventListener('drop', (event)=>{
        if(!(draggedNode instanceof HTMLElement) || level !== sourceLevel){
          return;
        }

        event.preventDefault();
        collectLevelNodes(level).forEach((node)=>{
          node.classList.remove('is-drop-target-before', 'is-drop-target-after');
        });
      });
    });

    qsa(safeNodeSelector, root).forEach((node)=>{
      const handle = qs(safeHandleSelector, node);
      if(!(node instanceof HTMLElement) || !(handle instanceof HTMLElement)){
        return;
      }

      node.draggable = false;

      node.addEventListener('dragstart', (event)=>{
        if(node !== armedNode){
          event.preventDefault();
          return;
        }

        event.stopPropagation();
        draggedNode = node;
        sourceLevel = node.parentElement instanceof HTMLElement ? node.parentElement : null;
        originalIds = sourceLevel ? collectOrderedIds(sourceLevel) : [];
        setDragActive(true);
        node.classList.add('is-dragging');
        emitStatus();

        if(event.dataTransfer){
          event.dataTransfer.effectAllowed = 'move';
          event.dataTransfer.setData('text/plain', node.getAttribute(safeNodeIdAttribute) || '');
        }
      });

      node.addEventListener('dragend', async (event)=>{
        event.stopPropagation();
        const level = sourceLevel;
        const previousOrder = [...originalIds];
        const nextOrder = level ? collectOrderedIds(level) : [];

        clearDragState();
        setDragActive(false);
        disarmNode(node);
        draggedNode = null;
        sourceLevel = null;
        originalIds = [];

        if(!(level instanceof HTMLElement) || previousOrder.join(',') === nextOrder.join(',')){
          return;
        }

        await persistLevelOrder(level, previousOrder);
      });

      handle.addEventListener('keydown', (event)=>{
        if(event.key === ' '){
          event.preventDefault();
        }
      });

      handle.addEventListener('pointerdown', ()=>{
        armNode(node);
      });

      handle.addEventListener('pointerup', ()=>{
        if(draggedNode !== node){
          disarmNode(node);
        }
      });

      handle.addEventListener('pointercancel', ()=>{
        if(draggedNode !== node){
          disarmNode(node);
        }
      });

      handle.addEventListener('blur', ()=>{
        if(draggedNode !== node){
          disarmNode(node);
        }
      });
    });
  };

  return {
    setup,
  };
}
